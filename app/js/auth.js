import { api } from "./api.js";

export async function loginUser(credentials) {
  const data = await api.post("/auth/login", credentials);

  if (data.token) {
    localStorage.setItem("token", data.token);
  }

  if (data.user) {
    localStorage.setItem("user", JSON.stringify(data.user));
  }

  return data;
}

export async function registerUser(userData) {
  return api.post("/auth/register", userData);
}

export function logoutUser() {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
  window.location.href = "login.html";
}

export function getCurrentUser() {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

export function isLoggedIn() {
  return Boolean(localStorage.getItem("token"));
}

export function requireAuth() {
  if (!isLoggedIn()) {
    window.location.href = "login.html";
  }
}
