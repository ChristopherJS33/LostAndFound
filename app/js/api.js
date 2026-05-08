import { CONFIG } from "./config.js";

async function request(endpoint, options = {}) {
  const token = localStorage.getItem("token");

  const requestConfig = {
    method: options.method || "GET",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  };

  if (options.body) {
    requestConfig.body = JSON.stringify(options.body);
  }

  const response = await fetch(
    `${CONFIG.API_BASE_URL}${endpoint}`,
    requestConfig
  );

  const contentType = response.headers.get("content-type") || "";
  const data = contentType.includes("application/json")
    ? await response.json()
    : await response.text();

  if (!response.ok) {
    const errorMessage =
      typeof data === "object" && data?.message
        ? data.message
        : "Request failed.";
    throw new Error(errorMessage);
  }

  return data;
}

export const api = {
  get(endpoint) {
    return request(endpoint);
  },
  post(endpoint, body) {
    return request(endpoint, { method: "POST", body });
  },
  put(endpoint, body) {
    return request(endpoint, { method: "PUT", body });
  },
  patch(endpoint, body) {
    return request(endpoint, { method: "PATCH", body });
  },
  delete(endpoint) {
    return request(endpoint, { method: "DELETE" });
  },
};
