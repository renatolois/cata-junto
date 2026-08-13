<script setup>
  import { ref } from 'vue';
  defineProps({
    title: {
      type: String,
      required: true
    },
    subTitle: {
      type: String,
      default: ''
    },
    imageUrl: {
      type: String,
      required: true
    },
    imageAlt: {
      type: String,
      required: true
    }
  })
</script>

<template>
  <section class="hero">
    <div class="hero-container">
      <div class="hero-content">
        <h1 class="hero-title">
          {{ title }}
        </h1>
      
        <p class="hero-subtitle">
          {{ subTitle }}
        </p>

        <div class="hero-actions">
          <a href="#" class="btn btn-primary">Como participar</a>
          <a href="#" class="btn btn-secondary">Sobre a gente</a>
        </div>
      </div>

      <div class="hero-media">
        <img 
          :src="imageUrl" 
          :alt="imageAlt"
          loading="lazy"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
:root {
  --bg-main: #f2f3f0;
  --bg-block: #ffffff;
  --text-main: #09090b;
  --text-alternative: #71717a;
  --primary: #2563eb;
  --primary-hover: #1d4ed8;
  --border: #e4e4e7;
  --shadow: 0 10.3px 30px -10px rgba(0, 0, 0, 0.04);
}

html.dark {
  --bg-main: #09090b;
  --bg-block: #121217;
  --text-main: #fafafa;
  --text-alternative: #a1a1aa;
  --primary: #3b82f6;
  --primary-hover: #60a5fa;
  --border: #27272a;
  --shadow: 0 10.3px 30px -10px rgba(0, 0, 0, 0.5);
}

.hero {
  width: 100%;
  padding: 1rem 0.75rem;
  box-sizing: border-box;
  background-color: var(--bg-main);
  color: var(--text-main);
}

.hero-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column-reverse;
  align-items: center;
  gap: 1.25rem;
  background-color: var(--bg-block);
  padding: 1.25rem;
  border-radius: 1rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
}

.hero-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.hero-title {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.2;
  margin: 0 0 0.5rem 0;
  color: var(--text-main);
}

.hero-subtitle {
  font-size: 1rem;
  line-height: 1.4;
  margin: 0 0 1.25rem 0;
  color: var(--text-alternative);
}

.hero-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
}

.btn {
  display: inline-block;
  padding: 0.625rem 1.25rem;
  border-radius: 0.5rem;
  text-decoration: none;
  font-weight: 600;
  text-align: center;
  transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary {
  background-color: var(--primary);
  color: #ffffff;
  border: 1px solid var(--primary);
}

.btn-primary:hover {
  background-color: var(--primary-hover);
  border-color: var(--primary-hover);
}

.btn-secondary {
  background-color: transparent;
  color: var(--text-main);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  background-color: var(--bg-main);
}

.hero-media {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-media img {
  width: 100%;
  height: auto;
  border-radius: 0.75rem;
  object-fit: cover;
  border: 1px solid var(--border);
}

@media (min-width: 690px) {
  .hero {
    padding: 2.5rem 1.5rem;
  }

  .hero-container {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    padding: 2.5rem;
  }

  .hero-content {
    flex: 1 1 50%;
    align-items: flex-start;
    text-align: left;
  }

  .hero-title {
    font-size: 2.25rem;
    margin-bottom: 0.75rem;
  }

  .hero-subtitle {
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
  }

  .hero-actions {
    flex-direction: row;
    gap: 0.75rem;
    width: auto;
  }

  .btn {
    padding: 0.75rem 1.5rem;
  }

  .hero-media {
    flex: 1 1 50%;
  }

  .hero-media img {
    max-width: 100%;
    min-height: 280px;
    max-height: 420px;
  }
}

@media (min-width: 1024px) {
  .hero {
    padding: 3.5rem 2rem;
  }

  .hero-container {
    gap: 3rem;
    padding: 3rem;
  }

  .hero-title {
    font-size: 2.75rem;
  }

  .hero-subtitle {
    font-size: 1.25rem;
  }

  .hero-media img {
    min-height: 350px;
    max-height: 480px;
  }
}
</style>