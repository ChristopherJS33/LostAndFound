export function showMessage(elementId, message, type = "info") {
  const element = document.getElementById(elementId);
  if (!element) return;

  element.textContent = message;
  element.className = `message ${type}`;
}

export function clearMessage(elementId) {
  const element = document.getElementById(elementId);
  if (!element) return;

  element.textContent = "";
  element.className = "message";
}

export function setLoading(button, isLoading, loadingText = "Loading...") {
  if (!button) return;

  if (isLoading) {
    button.dataset.originalText = button.textContent;
    button.textContent = loadingText;
    button.disabled = true;
  } else {
    button.textContent = button.dataset.originalText || "Submit";
    button.disabled = false;
  }
}

function getBadgeClass(status = "") {
  const value = status.toLowerCase();
  if (value === "lost") return "lost";
  if (value === "found") return "found";
  if (value === "claimed") return "claimed";
  return "lost";
}

export function createItemCard(item) {
  const badgeClass = getBadgeClass(item.status || "");
  const badgeLabel = item.status || "lost";

  return `
      <article class="item-card">
        <div class="item-card-top">
          <h3>${item.title}</h3>
          <span class="badge ${badgeClass}">${badgeLabel}</span>
        </div>
  
        <div class="item-meta">
          <div class="meta-row">
            <span>Category</span>
            <strong>${item.category || "Other"}</strong>
          </div>
          <div class="meta-row">
            <span>Location</span>
            <strong>${item.location || "Not provided"}</strong>
          </div>
        </div>
  
        <p class="item-description">${
          item.description || "No description provided."
        }</p>
  
        <a class="btn-secondary" href="item-details.html?id=${
          item.id
        }">View Details</a>
      </article>
    `;
}

export function createClaimCard(claim) {
  return `
      <article class="item-card">
        <div class="item-card-top">
          <h3>${claim.item_title || "Claim Request"}</h3>
          <span class="badge lost">${claim.status || "Pending"}</span>
        </div>
  
        <div class="item-meta">
          <div class="meta-row">
            <span>Claimant</span>
            <strong>${claim.claimant_name || "Unknown"}</strong>
          </div>
          <div class="meta-row">
            <span>Email</span>
            <strong>${claim.claimant_email || "Unknown"}</strong>
          </div>
        </div>
  
        <p class="item-description">${claim.reason || ""}</p>
      </article>
    `;
}
