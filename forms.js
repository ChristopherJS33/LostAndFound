import { loginUser, registerUser } from "./auth.js";
import { createLostItem, createFoundItem, claimItem } from "./items.js";
import { showMessage, clearMessage, setLoading } from "./ui.js";

export function setupLoginForm() {
  const form = document.getElementById("loginForm");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage("formMessage");

    const button = form.querySelector("button[type='submit']");

    const credentials = {
      email: form.email.value.trim(),
      password: form.password.value.trim(),
    };

    try {
      setLoading(button, true, "Logging in...");
      await loginUser(credentials);
      showMessage("formMessage", "Login successful.", "success");

      setTimeout(() => {
        window.location.href = "dashboard.html";
      }, 700);
    } catch (error) {
      showMessage("formMessage", error.message, "error");
    } finally {
      setLoading(button, false);
    }
  });
}

export function setupRegisterForm() {
  const form = document.getElementById("registerForm");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage("formMessage");

    const button = form.querySelector("button[type='submit']");

    const userData = {
      full_name: form.full_name.value.trim(),
      email: form.email.value.trim(),
      password: form.password.value.trim(),
      role: "user",
    };

    try {
      setLoading(button, true, "Creating account...");
      await registerUser(userData);
      showMessage(
        "formMessage",
        "Account created successfully. Please log in.",
        "success"
      );
      form.reset();
    } catch (error) {
      showMessage("formMessage", error.message, "error");
    } finally {
      setLoading(button, false);
    }
  });
}

export function setupLostItemForm() {
  const form = document.getElementById("lostItemForm");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage("formMessage");

    const button = form.querySelector("button[type='submit']");

    const itemData = {
      title: form.title.value.trim(),
      category: form.category.value,
      description: form.description.value.trim(),
      location: form.location.value.trim(),
      date_lost: form.date_lost.value,
      status: "lost",
    };

    try {
      setLoading(button, true, "Submitting...");
      await createLostItem(itemData);
      showMessage(
        "formMessage",
        "Lost item report submitted successfully.",
        "success"
      );
      form.reset();
    } catch (error) {
      showMessage("formMessage", error.message, "error");
    } finally {
      setLoading(button, false);
    }
  });
}

export function setupFoundItemForm() {
  const form = document.getElementById("foundItemForm");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage("formMessage");

    const button = form.querySelector("button[type='submit']");

    const itemData = {
      title: form.title.value.trim(),
      category: form.category.value,
      description: form.description.value.trim(),
      location: form.location.value.trim(),
      date_found: form.date_found.value,
      status: "found",
    };

    try {
      setLoading(button, true, "Submitting...");
      await createFoundItem(itemData);
      showMessage(
        "formMessage",
        "Found item report submitted successfully.",
        "success"
      );
      form.reset();
    } catch (error) {
      showMessage("formMessage", error.message, "error");
    } finally {
      setLoading(button, false);
    }
  });
}

export function setupClaimForm() {
  const form = document.getElementById("claimForm");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearMessage("claimMessage");

    const button = form.querySelector("button[type='submit']");
    const params = new URLSearchParams(window.location.search);
    const itemId = params.get("id");

    if (!itemId) {
      showMessage("claimMessage", "Missing item ID.", "error");
      return;
    }

    const claimData = {
      claimant_name: form.claimName.value.trim(),
      claimant_email: form.claimEmail.value.trim(),
      reason: form.claimReason.value.trim(),
    };

    try {
      setLoading(button, true, "Submitting claim...");
      await claimItem(itemId, claimData);
      showMessage("claimMessage", "Claim submitted successfully.", "success");
      form.reset();
    } catch (error) {
      showMessage("claimMessage", error.message, "error");
    } finally {
      setLoading(button, false);
    }
  });
}
