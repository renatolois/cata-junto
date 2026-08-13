<script setup>
  import { ref } from 'vue';
  
  const currentYear = new Date().getFullYear();
  const appName = ref('Cata-Junto');
</script>

<template>
  <footer>
    <div class="footer-content">
      <div class="footer-left">
        <p>&copy; {{ currentYear }} {{ appName }}. Todos os direitos reservados.</p>
      </div>
      <div class="footer-right">
        <nav>
          <ul>
            <li><RouterLink to="/sobre">sobre</RouterLink></li>
            <li><RouterLink to="/contato">contato</RouterLink></li>
          </ul>
        </nav>
      </div>
    </div>
  </footer>
</template>

<style scoped>
footer {
  background-color: var(--bg-block);
  border-top: 2px solid var(--border);
  padding: 1.5rem 4rem;
  margin: 1rem;
  border-radius: 0.5rem;
  color: var(--text-muted);
  font-size: 0.9rem;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.footer-right nav ul {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin: 0;
  padding: 0;
}

.footer-right nav ul li {
  border-radius: 0.2rem;
  cursor: pointer;
  overflow: hidden;
}

.footer-right nav ul li a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 0.4rem 0.8rem;
  color: inherit;
  text-decoration: none;
  box-sizing: border-box;
  transition: color 0.2s;
}

.footer-right nav ul li a:hover {
  color: var(--primary);
}

@media (max-width: 768px) {
  footer {
    padding: 1rem 1.5rem;
    margin: 0.5rem;
  }

  .footer-content {
    flex-direction: column-reverse;
    gap: 1rem;
    text-align: center;
  }

  .footer-right nav ul {
    gap: 1rem;
  }

  .footer-right nav ul li {
    width: 100%;
  }

  .footer-right nav ul li a {
    padding: 0.5rem 1rem;
  }
}

@media (max-width: 480px) {
  .footer-right nav ul {
    flex-direction: column;
    width: 100%;
    gap: 0.5rem;
  }
}
</style>