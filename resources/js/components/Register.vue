<template>
  <div class="container mx-auto p-4 max-w-md">
    <h1 class="text-2xl font-bold mb-4">Register</h1>
    <form @submit.prevent="register" class="space-y-4">
      <input v-model="name" type="text" placeholder="Name" required class="border p-2 w-full" />
      <input v-model="email" type="email" placeholder="Email" required class="border p-2 w-full" />
      <input v-model="password" type="password" placeholder="Password" required class="border p-2 w-full" />
      <input v-model="password_confirmation" type="password" placeholder="Confirm Password" required class="border p-2 w-full" />
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full">Register</button>
    </form>
      <router-link to="/" class="text-blue-500 hover:underline mt-4 block">Back</router-link>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import authService from '../services/authService';
import { useRouter } from 'vue-router';


    const name = ref('');
    const email = ref('');
    const password = ref('');
    const password_confirmation = ref('');
    const router = useRouter();

    const register = async () => {
      try {
        const response = await authService.register({
          name: name.value,
          email: email.value,
          password: password.value,
          password_confirmation: password_confirmation.value,
        });
        localStorage.setItem('auth_token', response.data.token);
        router.push('/');
      } catch (error) {
        console.error('Registration failed', error);
      }
    };


</script>
