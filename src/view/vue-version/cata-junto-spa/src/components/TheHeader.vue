<script setup>
  import { ref } from 'vue';
  import { useDarkMode } from '../composables/useDarkMode.js';
  
  const { isDark, changeTheme } = useDarkMode();

  const logoText = ref('Cata-Junto');
  const isMenuOpen = ref(false);

  const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
  };

  const closeMenu = () => {
    isMenuOpen.value = false;
  };
</script>

<template>
  <header>
    <div class="left">
      <div class="logo">
        <h2>{{ logoText }}</h2>
      </div>
    </div>
    
    <div class="right">
      <button @click="changeTheme" class="theme-btn" aria-label="Alternar Tema">
        <i class="fa-solid fa-circle-half-stroke"></i>
      </button>

      <button @click="toggleMenu" class="hamburger-btn" aria-label="Abrir Menu">
        <i :class="isMenuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'"></i>
      </button>

      <nav :class="{ 'nav-active': isMenuOpen }">
        <ul>
          <li @click="closeMenu">
            <RouterLink to="/contato">contato</RouterLink>
          </li>
          <li @click="closeMenu">
            <RouterLink to="/sobre">sobre</RouterLink>
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>

<style scoped>
header {
  background-color: var(--bg-block);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid var(--border);
  padding: 0 4rem;
  margin: 1rem;
  border-radius: 0.5rem;
  position: relative;
}

.left {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.right {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  gap: 2rem;
}

nav {
  display: flex;
}

ul {
  list-style: none;
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
  gap: 1rem;
  margin: 0;
  padding: 0;
}

ul li {
  display: block;
  width: auto;
  cursor: pointer;
}

ul li a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 0.5rem 1rem;
  text-decoration: none;
  color: inherit;
  box-sizing: border-box;
  cursor: pointer;
  border-radius: 0.375rem;
  transition: background-color 0.2s ease;
}

ul li a:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

button {
  background-color: var(--bg-block);
  border: 0.2rem solid var(--primary);
  color: inherit;
  cursor: pointer;
  padding: 0.5rem 0.8rem;
  border-radius: 2rem;
  transition: background-color 0.2s, color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hamburger-btn {
  display: none;
}

@media (max-width: 768px) {
  header {
    padding: 0.5rem 1rem;
    margin: 0.5rem;
  }

  .logo h2 {
    font-size: 1.25rem;
    margin: 0;
  }

  button {
    padding: 0.35rem 0.6rem;
    border-width: 1.5px;
    font-size: 0.85rem;
  }

  .hamburger-btn {
    display: flex;
  }

  nav {
    display: flex;
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background-color: var(--bg-alternative);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    padding: 0.75rem;
    box-shadow: var(--shadow);
    z-index: 50;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.3s ease, visibility 0.3s ease, background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
  }

  nav.nav-active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    border: 0.1rem solid var(--primary);
  }

  ul {
    flex-direction: column;
    width: 100%;
    gap: 0.5rem;
  }

  ul li {
    width: 100%;
  }

  ul li a {
    padding: 0.75rem 1rem;
  }
}

@media (max-width: 480px) {
  .right {
    gap: 0.4rem;
  }
}
</style>