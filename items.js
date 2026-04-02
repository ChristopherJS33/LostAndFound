import { api } from "./api.js";

export async function fetchAllItems(filters = {}) {
  const cleanedFilters = Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== "")
  );

  const query = new URLSearchParams(cleanedFilters).toString();
  const endpoint = query ? `/items?${query}` : "/items";

  return api.get(endpoint);
}

export async function fetchItemById(id) {
  return api.get(`/items/${id}`);
}

export async function createLostItem(itemData) {
  return api.post("/items/lost", itemData);
}

export async function createFoundItem(itemData) {
  return api.post("/items/found", itemData);
}

export async function fetchMyItems() {
  return api.get("/items/my-items");
}

export async function claimItem(itemId, claimData) {
  return api.post(`/claims/${itemId}`, claimData);
}

export async function fetchPendingClaims() {
  return api.get("/admin/claims");
}
