import {
  setupLoginForm,
  setupRegisterForm,
  setupLostItemForm,
  setupFoundItemForm,
  setupClaimForm,
} from "./forms.js";

import {
  fetchAllItems,
  fetchItemById,
  fetchMyItems,
  fetchPendingClaims,
} from "./items.js";

import { createItemCard, createClaimCard, showMessage } from "./ui.js";

import { getCurrentUser, logoutUser, requireAuth } from "./auth.js";

document.addEventListener("DOMContentLoaded", () => {
  setupLoginForm();
  setupRegisterForm();
  setupLostItemForm();
  setupFoundItemForm();
  setupClaimForm();
  setupBrowsePage();
  setupItemDetailsPage();
  setupDashboardPage();
  setupAdminPage();
  setupLogoutButtons();
});

async function setupBrowsePage() {
  const container = document.getElementById("itemsContainer");
  const searchForm = document.getElementById("searchForm");
  const clearFiltersBtn = document.getElementById("clearFiltersBtn");
  const resultsCount = document.getElementById("resultsCount");

  if (!container) return;

  async function loadItems(filters = {}) {
    try {
      const items = await fetchAllItems(filters);
      container.innerHTML = items.length
        ? items.map(createItemCard).join("")
        : "<p>No items found.</p>";

      if (resultsCount) {
        resultsCount.textContent = `${items.length} result(s)`;
      }
    } catch (error) {
      container.innerHTML = `<p class="error-text">${error.message}</p>`;
      if (resultsCount) {
        resultsCount.textContent = "";
      }
    }
  }

  await loadItems();

  if (searchForm) {
    searchForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const filters = {
        keyword: searchForm.searchKeyword.value.trim(),
        category: searchForm.searchCategory.value,
        status: searchForm.searchStatus.value,
      };

      await loadItems(filters);
    });
  }

  if (clearFiltersBtn && searchForm) {
    clearFiltersBtn.addEventListener("click", async () => {
      searchForm.reset();
      await loadItems();
    });
  }
}

async function setupItemDetailsPage() {
  const container = document.getElementById("itemDetails");
  if (!container) return;

  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");

  if (!id) {
    container.innerHTML = `<p class="error-text">Missing item ID.</p>`;
    return;
  }

  try {
    const item = await fetchItemById(id);

    container.innerHTML = `
        <section class="details-card">
          <div class="item-card-top" style="margin-bottom: 18px;">
            <h2>${item.title}</h2>
            <span class="badge ${(item.status || "lost").toLowerCase()}">${
      item.status || "lost"
    }</span>
          </div>
  
          <p class="section-text" style="margin-bottom: 22px;">
            Review the item details below before submitting a claim.
          </p>
  
          <div class="details-grid">
            <div class="details-block">
              <span class="details-label">Category</span>
              <strong>${item.category || "Other"}</strong>
            </div>
  
            <div class="details-block">
              <span class="details-label">Location</span>
              <strong>${item.location || "Not provided"}</strong>
            </div>
  
            <div class="details-block">
              <span class="details-label">Status</span>
              <strong>${item.status || "lost"}</strong>
            </div>
  
            <div class="details-block">
              <span class="details-label">Date</span>
              <strong>${
                item.date_lost || item.date_found || "Not provided"
              }</strong>
            </div>
  
            <div class="details-block" style="grid-column: 1 / -1;">
              <span class="details-label">Description</span>
              <strong>${item.description || "No description provided."}</strong>
            </div>
          </div>
        </section>
      `;
  } catch (error) {
    container.innerHTML = `<p class="error-text">${error.message}</p>`;
  }
}

async function setupDashboardPage() {
  const container = document.getElementById("myItemsContainer");
  const welcomeUser = document.getElementById("welcomeUser");

  if (!container && !welcomeUser) return;

  requireAuth();

  const user = getCurrentUser();

  if (welcomeUser && user) {
    welcomeUser.textContent = `Welcome, ${
      user.full_name || user.name || "User"
    }`;
  }

  if (!container) return;

  try {
    const items = await fetchMyItems();
    container.innerHTML = items.length
      ? items.map(createItemCard).join("")
      : "<p>You have not submitted any items yet.</p>";
  } catch (error) {
    showMessage("dashboardMessage", error.message, "error");
  }
}

async function setupAdminPage() {
  const claimsContainer = document.getElementById("adminClaimsContainer");
  const totalReports = document.getElementById("totalReports");
  const pendingClaimsCount = document.getElementById("pendingClaimsCount");
  const resolvedItemsCount = document.getElementById("resolvedItemsCount");

  if (!claimsContainer) return;

  try {
    const [claims, items] = await Promise.all([
      fetchPendingClaims(),
      fetchAllItems(),
    ]);

    claimsContainer.innerHTML = claims.length
      ? claims.map(createClaimCard).join("")
      : "<p>No pending claims.</p>";

    if (totalReports) {
      totalReports.textContent = Array.isArray(items) ? items.length : 0;
    }

    if (pendingClaimsCount) {
      pendingClaimsCount.textContent = Array.isArray(claims)
        ? claims.length
        : 0;
    }

    if (resolvedItemsCount) {
      const resolvedCount = Array.isArray(items)
        ? items.filter((item) => item.status === "claimed").length
        : 0;
      resolvedItemsCount.textContent = resolvedCount;
    }
  } catch (error) {
    claimsContainer.innerHTML = `<p class="error-text">${error.message}</p>`;
  }
}

function setupLogoutButtons() {
  document.querySelectorAll(".logout-btn").forEach((button) => {
    button.addEventListener("click", logoutUser);
  });
}
