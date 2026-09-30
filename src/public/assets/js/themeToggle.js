import {Storage} from './storage.js';


const themeBtn = document.getElementById('theme-btn');
themeBtn.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    Storage.write('darkmode', { 'enabled': isDark ? 'true' : 'false' }, true);
});