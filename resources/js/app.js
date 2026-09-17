import './bootstrap';

function autosizeDescriptionFields(root = document) {
    root.querySelectorAll('textarea[data-autosize]').forEach((el) => {
        el.style.height = 'auto';
        el.style.height = `${Math.max(el.scrollHeight, 32)}px`;
    });
}

document.addEventListener('input', (event) => {
    const el = event.target;
    if (el instanceof HTMLTextAreaElement && el.hasAttribute('data-autosize')) {
        el.style.height = 'auto';
        el.style.height = `${Math.max(el.scrollHeight, 32)}px`;
    }
});

document.addEventListener('livewire:init', () => {
    const schedule = () => queueMicrotask(() => autosizeDescriptionFields());
    Livewire.hook('morph.updated', schedule);
    Livewire.hook('commit', ({ succeed }) => succeed(schedule));
});

document.addEventListener('livewire:navigated', () => autosizeDescriptionFields());
document.addEventListener('DOMContentLoaded', () => autosizeDescriptionFields());
