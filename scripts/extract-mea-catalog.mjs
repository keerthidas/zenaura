import { getDocument, OPS } from "pdfjs-dist/legacy/build/pdf.mjs";
import crypto from "crypto";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import zlib from "zlib";

// Re-run:
//   cd scripts && npm install pdfjs-dist
//   node extract-mea-catalog.mjs
const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const PDF_PATH = process.env.MEA_PDF || "C:/Users/keert/Downloads/MEA 2026.pdf";
const OUT_DIR = path.join(ROOT, "database/seeders/data/mea-2026");
const START = Number(process.env.MEA_START || 96);
const END = Number(process.env.MEA_END || 495);

const CODE_RE = /^[A-Z]{2,5}-[A-Z0-9]+(?:-[A-Z0-9]+)+$/;
const PRICE_RE = /^\d{1,7}(?:,\d{3})*\.\d{2}$/;
const FINISH_TOKEN_RE = /^(?:[A-Z]{1,3}-)?[A-Z]{1,4}(?:-[A-Z0-9]{1,3})?$/;

const NOISE = new Set([
  "NEW",
  "US$",
  "MIXERS",
  "ACCESSORIES",
  "KITCHEN",
  "SHOWERS",
  "BATHS",
  "WCS",
  "BASINS",
  "FURNITURE",
  "MIRRORS",
  "LIGHTING",
  "HEATING",
  "WELLNESS",
  "SPA",
  "OUTDOOR",
  "FINISHES:",
  "FINISHES",
]);

const DEFAULT_FINISH_NAMES = {
  CP: "Chrome",
  NB: "Brushed Nickel",
  AN: "Anthracite",
  SBZ: "Soft Bronze",
  OYS: "PVD Oyster",
  MB: "Matt Black",
  SS: "Stainless Steel",
  BS: "Brushed Stainless Steel",
  "CO-C": "Copper",
  "A-CP": "Chrome",
  "A-MB": "Matt Black",
  WH: "White",
  BLK: "Black",
  GM: "Gun Metal",
  BR: "Brass",
  GLD: "Gold",
  PG: "Polished Gold",
  BG: "Brushed Gold",
};

function pngChunk(type, data) {
  const len = Buffer.alloc(4);
  len.writeUInt32BE(data.length);
  const td = Buffer.concat([Buffer.from(type), data]);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(zlib.crc32(td) >>> 0);
  return Buffer.concat([len, td, crc]);
}

function rgbToPng(width, height, rgb) {
  const stride = width * 3 + 1;
  const raw = Buffer.alloc(stride * height);
  for (let y = 0; y < height; y++) {
    raw[stride * y] = 0;
    rgb.copy(raw, stride * y + 1, y * width * 3, (y + 1) * width * 3);
  }
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8;
  ihdr[9] = 2;
  return Buffer.concat([
    Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]),
    pngChunk("IHDR", ihdr),
    pngChunk("IDAT", zlib.deflateSync(raw)),
    pngChunk("IEND", Buffer.alloc(0)),
  ]);
}

function toRgb(img) {
  const w = img?.width;
  const h = img?.height;
  const data = img?.data;
  const kind = img?.kind;
  if (!w || !h || !data) return null;
  if (kind === 2 && data.length >= w * h * 3) {
    return { w, h, rgb: Buffer.from(data.buffer, data.byteOffset, w * h * 3) };
  }
  if (kind === 3 && data.length >= w * h * 4) {
    const rgb = Buffer.alloc(w * h * 3);
    for (let i = 0, j = 0; i < w * h * 4; i += 4) {
      rgb[j++] = data[i];
      rgb[j++] = data[i + 1];
      rgb[j++] = data[i + 2];
    }
    return { w, h, rgb };
  }
  if (kind === 1 && data.length >= w * h) {
    const rgb = Buffer.alloc(w * h * 3);
    for (let i = 0; i < w * h; i++) {
      rgb[i * 3] = rgb[i * 3 + 1] = rgb[i * 3 + 2] = data[i];
    }
    return { w, h, rgb };
  }
  return null;
}

function getObj(page, name, ms = 3000) {
  return Promise.race([
    new Promise((resolve) => {
      try {
        page.objs.get(name, resolve);
      } catch {
        resolve(null);
      }
    }),
    new Promise((resolve) => setTimeout(() => resolve(null), ms)),
  ]);
}

function multiply(m, n) {
  return [
    m[0] * n[0] + m[2] * n[1],
    m[1] * n[0] + m[3] * n[1],
    m[0] * n[2] + m[2] * n[3],
    m[1] * n[2] + m[3] * n[3],
    m[0] * n[4] + m[2] * n[5] + m[4],
    m[1] * n[4] + m[3] * n[5] + m[5],
  ];
}

function parsePrice(str) {
  return parseFloat(str.replace(/,/g, ""));
}

function isCode(str) {
  return CODE_RE.test(str.trim());
}

function isPrice(str) {
  return PRICE_RE.test(str.trim());
}

function isFinishToken(str) {
  const s = str.trim();
  if (!s || s.length > 8) return false;
  if (isCode(s) || isPrice(s) || s === "XXX") return false;
  return FINISH_TOKEN_RE.test(s);
}

function splitFinishes(str) {
  return str
    .split(",")
    .map((s) => s.trim())
    .filter(isFinishToken);
}

function isFinishItem(str) {
  const parts = str.split(",").map((s) => s.trim()).filter(Boolean);
  return parts.length > 0 && parts.every(isFinishToken);
}

function cleanText(s) {
  return s
    .replace(/[\u201C\u201D\u2018\u2019]/g, '"')
    .replace(/\u2013|\u2014/g, "-")
    .replace(/\s+/g, " ")
    .trim();
}

function isNoise(str) {
  const s = cleanText(str);
  if (!s) return true;
  if (NOISE.has(s.toUpperCase())) return true;
  if (/^\d{2,3}$/.test(s)) return true;
  if (/^all prices include vat$/i.test(s)) return true;
  if (/^discover more at$/i.test(s)) return true;
  if (/sanipexgroup/i.test(s)) return true;
  if (/^replace xxx/i.test(s)) return true;
  if (/^\(replace xxx/i.test(s)) return true;
  if (/^recommended accessories$/i.test(s)) return true;
  if (/^for further details/i.test(s)) return true;
  if (/^for showering accessories/i.test(s)) return true;
  if (/^\(for hand shower/i.test(s)) return true;
  if (/^pages?\s+\d+/i.test(s)) return true;
  return false;
}

function clusterColumns(xs, gap = 50) {
  const sorted = [...new Set(xs.map((x) => Math.round(x)))].sort((a, b) => a - b);
  const cols = [];
  for (const x of sorted) {
    if (!cols.length || x - cols[cols.length - 1] > gap) cols.push(x);
  }
  return cols;
}

function columnIndex(x, colXs) {
  let idx = 0;
  for (let i = 0; i < colXs.length; i++) {
    if (x + 8 >= colXs[i]) idx = i;
  }
  return idx;
}

function groupRows(items, tol = 4) {
  const sorted = [...items].sort((a, b) => b.y - a.y || a.x - b.x);
  const rows = [];
  for (const item of sorted) {
    const row = rows.find((r) => Math.abs(r.y - item.y) <= tol);
    if (row) {
      row.items.push(item);
      row.y = (row.y * (row.items.length - 1) + item.y) / row.items.length;
    } else {
      rows.push({ y: item.y, items: [item] });
    }
  }
  return rows;
}

function expandCode(template, finish) {
  if (template.includes("-XXX")) return template.replace("-XXX", `-${finish}`);
  if (template.endsWith("XXX")) return template.slice(0, -3) + finish;
  return `${template}-${finish}`;
}

function parseFinishLegend(items) {
  const map = { ...DEFAULT_FINISH_NAMES };
  const legend = items.filter((i) => i.y < 140);
  const tokens = legend.filter((i) => isFinishToken(i.str)).sort((a, b) => a.x - b.x || b.y - a.y);
  for (const tok of tokens) {
    const nameParts = legend
      .filter((i) => Math.abs(i.x - tok.x) < 28 && i.y < tok.y - 2 && i.y > tok.y - 30 && !isFinishToken(i.str) && !isNoise(i.str))
      .sort((a, b) => b.y - a.y)
      .map((i) => i.str.trim());
    if (nameParts.length) map[tok.str.trim()] = nameParts.join(" ");
  }
  return map;
}

function pageCollection(items) {
  const top = items.filter((i) => i.y > 785 && !isNoise(i.str) && !isCode(i.str) && i.str.length > 2);
  top.sort((a, b) => b.y - a.y || a.x - b.x);
  const names = top.map((i) => cleanText(i.str)).filter((s) => !/^\d+$/.test(s));
  return names[0] || "";
}

function isPartLabel(str) {
  return /^(trim part|concealed part)$/i.test(str.trim());
}

function aedFromPrices(priceItems) {
  const ordered = [...priceItems].sort((a, b) => a.x - b.x);
  if (!ordered.length) return null;
  if (ordered.length === 1) return parsePrice(ordered[0].str);
  const left = parsePrice(ordered[0].str);
  const right = parsePrice(ordered[1].str);
  return Math.max(left, right);
}

function priceTableBottom(codeItem, colItems, nextCodeY) {
  const rows = groupRows(colItems.filter((it) => it.y <= codeItem.y + 4 && it.y > (nextCodeY ?? 120)));
  let bottom = codeItem.y;
  for (const row of rows) {
    const useful = row.items.some((it) => {
      const s = it.str.trim();
      return isPrice(s) || isFinishItem(s) || s === "US$" || isPartLabel(s) || s === codeItem.str.trim();
    });
    const desc = row.items.some((it) => {
      const s = it.str.trim();
      return !isPrice(s) && !isFinishItem(s) && s !== "US$" && !isCode(s) && !isPartLabel(s) && !isNoise(s);
    });
    if (row.y > codeItem.y + 4) continue;
    if (useful) bottom = Math.min(bottom, row.y);
    else if (desc && row.y < codeItem.y - 8) break;
  }
  return bottom - 3;
}

function parseBlock(codeItem, blockItems) {
  const rows = groupRows(blockItems);
  const rowPairs = [];
  const finishOnly = [];
  const aedOnly = [];
  let extraLabel = "";

  for (const row of rows) {
    const ordered = [...row.items].sort((a, b) => a.x - b.x);
    const finishes = [];
    const priceItems = [];
    const labels = [];
    for (const it of ordered) {
      const s = it.str.trim();
      if (s === codeItem.str.trim() || s === "US$" || isNoise(s)) continue;
      if (isPrice(s)) {
        priceItems.push(it);
        continue;
      }
      if (isFinishItem(s)) {
        finishes.push(...splitFinishes(s));
        continue;
      }
      if (isPartLabel(s)) labels.push(cleanText(s));
    }
    if (labels.length) extraLabel = [extraLabel, ...labels].filter(Boolean).join(" ");
    const aed = aedFromPrices(priceItems);
    if (finishes.length && aed != null) {
      rowPairs.push({ finishes, aed });
    } else if (finishes.length && aed == null) {
      finishOnly.push(...finishes);
    } else if (!finishes.length && aed != null) {
      aedOnly.push(aed);
    }
  }

  if (rowPairs.length) {
    return { pairs: rowPairs, extraLabel };
  }
  if (finishOnly.length && aedOnly.length) {
    const pairs = [];
    if (finishOnly.length === aedOnly.length) {
      finishOnly.forEach((f, i) => pairs.push({ finishes: [f], aed: aedOnly[i] }));
    } else {
      const n = Math.min(finishOnly.length, aedOnly.length);
      for (let i = 0; i < n; i++) pairs.push({ finishes: [finishOnly[i]], aed: aedOnly[i] });
    }
    return { pairs, extraLabel };
  }
  if (aedOnly.length) {
    return { pairs: [{ finishes: [], aed: aedOnly[0] }], extraLabel };
  }
  return { pairs: [], extraLabel };
}

async function extractPage(pdf, n, imageStore) {
  const page = await pdf.getPage(n);
  const content = await page.getTextContent();
  const ops = await page.getOperatorList();

  const items = content.items
    .map((it) => ({
      str: it.str,
      x: it.transform[4],
      y: it.transform[5],
      w: it.width || 0,
    }))
    .filter((it) => it.str && it.str.trim());

  let ctm = [1, 0, 0, 1, 0, 0];
  const stack = [];
  const images = [];
  for (let i = 0; i < ops.fnArray.length; i++) {
    const fn = ops.fnArray[i];
    const args = ops.argsArray[i];
    if (fn === OPS.save) stack.push(ctm.slice());
    else if (fn === OPS.restore) ctm = stack.pop() || ctm;
    else if (fn === OPS.transform) ctm = multiply(ctm, args);
    else if (fn === OPS.paintImageXObject) {
      const w = Math.abs(ctm[0]);
      const h = Math.abs(ctm[3]);
      if (w < 48 || h < 40 || ctm[5] < 120) continue;
      const img = await getObj(page, args[0]);
      const rgb = toRgb(img);
      if (!rgb || rgb.w < 70) continue;
      images.push({
        name: args[0],
        x: ctm[4],
        y: ctm[5],
        w,
        h,
        iw: rgb.w,
        ih: rgb.h,
        rgb: rgb.rgb,
      });
    }
  }

  const codes = items.filter((it) => isCode(it.str));
  if (!codes.length) {
    return { products: [], skipped: true };
  }

  const colXs = clusterColumns(codes.map((c) => c.x));
  const collection = pageCollection(items);
  const finishNames = parseFinishLegend(items);
  const products = [];

  for (let col = 0; col < colXs.length; col++) {
    const colCodes = codes
      .filter((c) => columnIndex(c.x, colXs) === col)
      .sort((a, b) => b.y - a.y);

    const colItems = items.filter((it) => columnIndex(it.x, colXs) === col);
    const colImages = images.filter((im) => columnIndex(im.x + im.w / 2, colXs) === col);

    let prevTableBottom = 805;
    let prevDescription = "";
    for (let i = 0; i < colCodes.length; i++) {
      const codeItem = colCodes[i];
      const next = colCodes[i + 1];
      const tableBottom = priceTableBottom(codeItem, colItems, next?.y);
      const yTop = prevTableBottom;

      const descItems = colItems
        .filter((it) => it.y > codeItem.y + 6 && it.y < yTop - 1)
        .filter((it) => !isCode(it.str) && !isPrice(it.str) && !isFinishItem(it.str) && !isNoise(it.str) && !isPartLabel(it.str))
        .sort((a, b) => b.y - a.y || a.x - b.x);

      const descLines = [];
      for (const it of descItems) {
        const t = cleanText(it.str);
        if (!t) continue;
        if (collection && t.toUpperCase() === collection.toUpperCase()) continue;
        if (descLines.at(-1) === t) continue;
        descLines.push(t);
      }
      let description = descLines.join(" ").replace(/\s+/g, " ").trim();
      description = description.replace(/\s+\/\s+/g, " / ");

      const blockItems = colItems.filter((it) => it.y <= codeItem.y + 4 && it.y >= tableBottom);
      const { pairs, extraLabel } = parseBlock(codeItem, blockItems);
      if (extraLabel) {
        const label = extraLabel.replace(/\s+/g, " ").trim();
        if (isPartLabel(label) && prevDescription) {
          description = `${prevDescription} ${label}`.trim();
        } else if (label && !description.includes(label)) {
          description = `${description} ${label}`.trim();
        }
      }

      if (!description) description = collection || "Catalog item";
      prevTableBottom = tableBottom;
      prevDescription = description;

      const template = codeItem.str.trim();
      const hasXxx = template.includes("XXX");

      let img = null;
      const imgCandidates = colImages.filter((im) => {
        const mid = im.y + im.h / 2;
        return mid < yTop + 10 && mid > tableBottom - 10;
      });
      if (imgCandidates.length) {
        imgCandidates.sort((a, b) => Math.abs(a.y + a.h / 2 - (codeItem.y + 40)) - Math.abs(b.y + b.h / 2 - (codeItem.y + 40)));
        img = imgCandidates[0];
      }

      let imageName = null;
      if (img) {
        const hash = crypto.createHash("sha1").update(img.rgb).digest("hex").slice(0, 16);
        imageName = `${hash}.png`;
        if (!imageStore.has(imageName)) {
          imageStore.set(imageName, rgbToPng(img.iw, img.ih, img.rgb));
        }
      }

      const emit = (code, aed, finish) => {
        const finishName = finish ? finishNames[finish] || finish : "";
        let desc = description;
        if (collection && !desc.toUpperCase().startsWith(collection.toUpperCase())) {
          desc = `${collection} — ${desc}`;
        }
        if (finishName && !desc.includes(finishName)) {
          desc = `${desc}. Finish: ${finishName}`;
        }
        products.push({
          code,
          description: desc.slice(0, 2000),
          selling_price: Number(aed.toFixed(2)),
          actual_price: 0,
          unit: "pcs",
          image: imageName ? `images/${imageName}` : null,
          page: n,
          collection,
          finish: finish || null,
        });
      };

      if (!pairs.length) {
        continue;
      }

      if (!hasXxx) {
        emit(template, pairs[0].aed, pairs[0].finishes[0] || null);
        continue;
      }

      for (const pair of pairs) {
        const finishes = pair.finishes.length ? pair.finishes : ["STD"];
        for (const finish of finishes) {
          emit(expandCode(template, finish), pair.aed, finish);
        }
      }
    }
  }

  return { products, skipped: false };
}

async function main() {
  fs.mkdirSync(path.join(OUT_DIR, "images"), { recursive: true });
  console.log(`Opening PDF, extracting pages ${START}-${END}...`);

  const pdf = await getDocument({
    url: PDF_PATH,
    disableWorker: true,
    isEvalSupported: false,
  }).promise;

  const imageStore = new Map();
  const products = [];
  const byCode = new Map();
  let skippedPages = 0;

  const last = Math.min(END, pdf.numPages);
  for (let n = START; n <= last; n++) {
    try {
      const { products: pageProducts, skipped } = await extractPage(pdf, n, imageStore);
      if (skipped) skippedPages++;
      for (const p of pageProducts) {
        if (byCode.has(p.code)) continue;
        byCode.set(p.code, p);
        products.push(p);
      }
      if (n === START || n === last || n % 10 === 0) {
        console.log(`page ${n} +${pageProducts.length} total ${products.length} images ${imageStore.size}`);
      }
    } catch (err) {
      console.error(`page ${n} failed:`, err.message);
    }
  }

  const imgDir = path.join(OUT_DIR, "images");
  for (const [name, buf] of imageStore) {
    fs.writeFileSync(path.join(imgDir, name), buf);
  }

  const jsonPath = path.join(OUT_DIR, "products.json");
  fs.writeFileSync(jsonPath, JSON.stringify(products, null, 0));

  const withImage = products.filter((p) => p.image).length;
  const sample = products.filter((p) => p.code.startsWith("BDM-CRL-301S") || p.code.startsWith("BDM-CRL-302S"));
  fs.writeFileSync(path.join(OUT_DIR, "sample-preview.json"), JSON.stringify(sample, null, 2));

  const summary = {
    pages: `${START}-${last}`,
    skippedPages,
    skuCount: products.length,
    withImage,
    uniqueImages: imageStore.size,
    collections: [...new Set(products.map((p) => p.collection).filter(Boolean))],
  };
  fs.writeFileSync(path.join(OUT_DIR, "summary.json"), JSON.stringify(summary, null, 2));
  console.log(JSON.stringify(summary, null, 2));
  console.log("sample", sample.map((s) => `${s.code} ${s.selling_price}`).join(" | "));

  await pdf.destroy();
  process.exit(0);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
