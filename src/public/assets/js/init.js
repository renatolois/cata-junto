import {Storage} from './storage.js';

const darkmode = Storage.read('darkmode', true);

if(darkmode === null) {
  Storage.write('darkmode', {'enabled': 'true'}, true);
  document.documentElement.classList.add('dark');
} 
else if(darkmode.enabled === 'true') {
  document.documentElement.classList.add('dark');
} 
else {
  document.documentElement.classList.remove('dark');
}