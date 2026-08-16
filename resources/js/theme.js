// Theme initializer and toggle helper
const root = document.documentElement;

function applyTheme(theme) {
  if (theme === 'dark') root.classList.add('dark');
  else root.classList.remove('dark');
}

const saved = localStorage.getItem('theme');
if (saved) {
  applyTheme(saved);
} else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
  applyTheme('dark');
} else {
  applyTheme('light');
}

window.toggleTheme = function () {
  const isDark = root.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
  return isDark;
};

export default null;
