import axios from 'axios';

const apiClient = axios.create({
  baseURL: '/api',
  withCredentials: false,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export default {
  register(user) {
    return apiClient.post('/register', user);
  },
  login(user) {
    return apiClient.post('/login', user);
  },
  logout() {
    const token = localStorage.getItem('auth_token');
    return apiClient.post('/logout', {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
  }
};
