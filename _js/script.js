/* internal classes */
import Page from './Page';

const page = new Page();
document.addEventListener('DOMContentLoaded', () => {
    page.ready();
});

window.addEventListener('load', e => {
    page.load();    
});
