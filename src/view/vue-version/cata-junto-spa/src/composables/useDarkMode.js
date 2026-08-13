import { ref, onMounted } from 'vue'

export function useDarkMode() {
  const isDark = ref(false)

  onMounted(() => {
    let darkMode = localStorage.getItem('darkMode')
    if (darkMode == null) {
      localStorage.setItem('darkMode', 'false')
      darkMode = 'false'
    }

    isDark.value = darkMode === 'true'
    if (isDark.value) {
      document.documentElement.classList.add('dark')
    }
  })

  function changeTheme() {
    isDark.value = !isDark.value
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('darkMode', isDark.value ? 'true' : 'false')
  }

  return {
    isDark,
    changeTheme
  }
}