<script setup>
  import TheHeader from './components/TheHeader.vue';
  import TheHero from './components/TheHero.vue';
  import TheFooter from './components/TheFooter.vue';
</script>

<template>
  <div class="app-layout">
    <TheHeader />
    <main class="app-content">
      <TheHero 
        title="Recicle Conosco"
        subTitle="Ajude o meio ambiente e ganhe recompensas por isso"
        imageUrl="https://mecanofar.com.br/wp-content/uploads/2025/06/image-12-1.jpg"
        imageAlt="Image de Reciclagem"
      />
    </main>
    
    <TheFooter />
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.app-content {
  flex: 1;
}
</style>