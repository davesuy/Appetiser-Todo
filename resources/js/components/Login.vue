<template>
  <div class="container mx-auto p-4 max-w-md">
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <form @submit.prevent="login" class="space-y-4">
      <input v-model="email" type="email" placeholder="Email" required class="border p-2 w-full" />
      <input v-model="password" type="password" placeholder="Password" required class="border p-2 w-full" />
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full">Login</button>
    </form>
    <router-link to="/" class="text-blue-500 hover:underline mt-4 block">Back</router-link>
  </div>
</template>

<script setup>

import { ref } from 'vue';
import authService from '../services/authService';
import { useRouter } from 'vue-router';


const email = ref('');
const password = ref('');
const router = useRouter();

const login = async () => {
  try {
    const response = await authService.login({ email: email.value, password: password.value });
    localStorage.setItem('auth_token', response.data.token);
    router.push('/');
  } catch (error) {
    console.error('Login failed', error);
  }
};

</script>
