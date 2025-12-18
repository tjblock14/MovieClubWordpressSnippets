/***************************************************************************************************
 * Movie Club — Inline/Modal Edit Script (movies + TV shows + TV seasons/episodes)
 * - Ratings: inline number input (Enter/blur saves)
 * - Reviews: fullscreen modal textarea (Save/Cancel)
 * - Only one cell can be edited at a time
 *
 * EXPECTED DATA ATTRIBUTES ON CELLS:
 *  - data-review-type="movie" | "tv"
 *  - data-reviewer="trevor" (must match localStorage username)
 *  - data-id="<item id>"                 // movie id OR tv show id OR season id OR episode id
 *  - data-review-id="<review pk>"        // IMPORTANT: if present, we PATCH directly
 *
 * TV ONLY:
 *  - data-target-type="show"|"season"|"episode"   // default = "show"
 *  - data-couple-slug="TrevorTaylor" (or whatever your backend stores)
 *  - data-tv-show-title="Stranger Things" (optional)
 *  - data-movie-title="Inception" (optional)
 ***************************************************************************************************/

/* ===== Global state ===== */
let activeCell = null;
let modalMeta  = null;
let mcBackdrop = null;
let mcModal    = null;

// Small toast
let mcToast = null;
let mcToastTimer = null;

document.addEventListener("DOMContentLoaded", () => {
  console.log("Review edit script loaded");

  const style = document.createElement("style");
  style.textContent = `
    .blocked-cell { cursor: not-allowed; opacity: .65; }
    .editing-cell { outline: 3px solid #4f46e5; outline-offset: -3px; }

    .mc-toast {
      position: fixed;
      right: 18px;
      bottom: 18px;
      z-index: 10000;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid rgba(148,163,184,.35);
      background: rgba(15,23,42,.92);
      color: #e2e8f0;
      font-size: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,.35);
      display: none;
      max-width: min(520px, 92vw);
      line-height: 1.25;
    }
    .mc-toast.open { display: block; }
  `;
  document.head.appendChild(style);

  const modalStyle = document.createElement("style");
  modalStyle.textContent = `
    .mc-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: none; z-index: 9998; }
    .mc-modal { position: fixed; inset: 0; display: none; z-index: 9999; align-items: center; justify-content: center; }
    .mc-modal.open, .mc-modal-backdrop.open { display: flex; }
    .mc-card { width: min(900px, 92vw); max-height: 88vh; background: #111; color: #f8fafc; border-radius: 16px;
               box-shadow: 0 20px 60px rgba(0,0,0,.5); display: grid; grid-template-rows: auto 1fr auto; }
    .mc-hd { padding: 18px 20px; border-bottom: 1px solid #334155; font-size: 18px; font-weight: 700;
             display:flex; justify-content:space-between; gap:12px; }
    .mc-bd { padding: 16px; overflow:auto; }
    .mc-ft { padding: 12px 16px; border-top: 1px solid #334155; display:flex; gap:10px; justify-content:flex-end; }
    .mc-txt { width:100%; min-height: 52vh; font-size: 15px; line-height: 1.45; padding: 12px; border-radius: 10px;
              border:1px solid #334155; background:#0b1220; color:#e2e8f0; }
    .mc-btn { padding: 10px 16px; border-radius: 10px; border:1px solid #334155; background:#1f2937; color:#e5e7eb; cursor:pointer; }
    .mc-btn.primary { background:#4f46e5; border-color:#4f46e5; color:white; }
  `;
  document.head.appendChild(modalStyle);

  // Build toast once
  mcToast = document.createElement("div");
  mcToast.className = "mc-toast";
  document.body.appendChild(mcToast);

  // Build modal once
  mcBackdrop = document.createElement("div");
  mcBackdrop.className = "mc-modal-backdrop";

  mcModal = document.createElement("div");
  mcModal.className = "mc-modal";
  mcModal.innerHTML = `
    <div class="mc-card">
      <div class="mc-hd">
        <div id="mc-title">Edit Review</div>
        <button id="mc-close" class="mc-btn">Close</button>
      </div>
      <div class="mc-bd">
        <textarea id="mc-textarea" class="mc-txt" placeholder="Type your full review..."></textarea>
      </div>
      <div class="mc-ft">
        <button id="mc-cancel" class="mc-btn">Cancel</button>
        <button id="mc-save" class="mc-btn primary">Save</button>
      </div>
    </div>
  `;
  document.body.append(mcBackdrop, mcModal);

  document.getElementById("mc-cancel").addEventListener("click", () => { closeReviewModal(); closeActiveEditor(true); });
  document.getElementById("mc-close").addEventListener("click",  () => { closeReviewModal(); closeActiveEditor(true); });

  document.getElementById("mc-save").addEventListener("click", async () => {
    if (!modalMeta) return;

    const cell = modalMeta.cell;

    // Save previous value so we can revert if API fails
    const prev = cell.textContent;

    const text = document.getElementById("mc-textarea").value;
    cell.textContent = text; // optimistic UI

    const ok = await updateReviewFromCell(cell, "rating_justification", text, prev);
    if (!ok) {
      cell.textContent = prev; // revert on failure
      return;
    }

    closeReviewModal();
    closeActiveEditor(false);
  });

  document.getElementById("mc-textarea").addEventListener("keydown", (e) => {
    if (e.key === "Escape") { e.preventDefault(); document.getElementById("mc-cancel").click(); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); document.getElementById("mc-save").click(); }
  });

  // Attach click handlers only to owned cells
  document.querySelectorAll(".rating-cell, .review-cell").forEach((cell) => {
    if (cellIsOwnedByUser(cell)) {
      cell.classList.remove("blocked-cell");
      cell.addEventListener("click", () => makeCellEditable(cell));
    } else {
      cell.classList.add("blocked-cell");
    }
  });

  // ✅ Apply rating colors on initial page load (so no refresh needed later)
  document.querySelectorAll(".rating-cell").forEach((cell) => {
    const v = cell.textContent.trim();
    applyRatingColorToCell(cell, v);
  });
});

/* === UI helpers === */
function toast(msg, ms = 1800) {
  if (!mcToast) return;
  mcToast.textContent = msg;
  mcToast.classList.add("open");
  if (mcToastTimer) clearTimeout(mcToastTimer);
  mcToastTimer = setTimeout(() => mcToast.classList.remove("open"), ms);
}

/* === Rating color helpers ===
   NOTE: Top bands match your PHP snippet exactly (>=9, >=7.5, >=6).
   Replace lower thresholds/colors below to match the rest of your PHP function if needed.
*/
function ratingToBg(r) {
  if (r === "" || r === null || typeof r === "undefined") return "";
  const n = Number(r);
  if (!Number.isFinite(n)) return "";

  if (n >= 9.0) return "#3C8D40";   // dark green
  if (n >= 7.5) return "#5F9F61";   // light green
  if (n >= 6.0) return "#A6CDA8";   // very light green

  // Placeholder lower bands (adjust to match your PHP)
  if (n >= 4.0) return "#E7D39B";   // yellow-ish
  if (n >= 2.5) return "#E6A07A";   // orange-ish
  return "#D96262";                // red-ish
}

function applyRatingColorToCell(cell, ratingValue) {
  const bg = ratingToBg(ratingValue);

  // Apply inline so it updates instantly
  cell.style.backgroundColor = bg;

  // Optional: keep text readable on dark greens
  const n = Number(ratingValue);
  if (bg && Number.isFinite(n) && n >= 7.5) {
    cell.style.color = "#ffffff";
  } else {
    cell.style.color = "";
  }
}

/* === Helpers === */
function getCurrentUser() {
  return (localStorage.getItem("username") || "").toLowerCase();
}

function cellIsOwnedByUser(cell) {
  const reviewer = (cell.dataset.reviewer || "").toLowerCase();
  return reviewer === getCurrentUser();
}

function normalizeTargetType(tt) {
  const v = String(tt || "show").trim().toLowerCase();
  if (v === "show" || v === "season" || v === "episode") return v;
  return "show";
}

function openReviewModal({ cell, reviewer, initialText, titleForModal }) {
  modalMeta = { cell, reviewer };
  document.getElementById("mc-title").textContent = `Edit Review — ${reviewer} (${titleForModal})`;
  const ta = document.getElementById("mc-textarea");
  ta.value = initialText || "";
  mcBackdrop.classList.add("open");
  mcModal.classList.add("open");
  setTimeout(() => ta.focus(), 0);
}

function closeReviewModal() {
  mcBackdrop.classList.remove("open");
  mcModal.classList.remove("open");
  modalMeta = null;
}

function closeActiveEditor(discard) {
  if (!activeCell) return;
  activeCell.classList.remove("editing-cell");

  const input = activeCell.querySelector("input");
  if (input && discard) {
    activeCell.textContent = input.defaultValue || "";
    // ✅ restore color if rating cell
    if (activeCell.classList.contains("rating-cell")) {
      applyRatingColorToCell(activeCell, activeCell.textContent.trim());
    }
  }
  activeCell = null;
}

function makeCellEditable(cell) {
  if (!cellIsOwnedByUser(cell)) return;

  if (activeCell && activeCell !== cell) closeActiveEditor(true);
  if (activeCell === cell) return;

  activeCell = cell;
  cell.classList.add("editing-cell");

  const reviewer = cell.dataset.reviewer || "";
  const field = cell.classList.contains("rating-cell") ? "rating" : "rating_justification";

  if (field === "rating") {
    const currentValue = cell.textContent.trim();
    const input = document.createElement("input");
    input.type = "number";
    input.step = "0.1";
    input.min = "0";
    input.max = "10";
    input.value = currentValue || "";
    input.defaultValue = currentValue || "";
    input.style.width = "70px";
    input.style.textAlign = "center";
    input.style.fontWeight = "700";

    let alreadySaved = false;

    async function saveAndClose() {
      if (alreadySaved) return;
      alreadySaved = true;

      const prev = input.defaultValue || "";
      const newValue = input.value.trim();
      const clamped = newValue === "" ? "" : Math.max(0, Math.min(10, parseFloat(newValue)));

      cell.textContent = clamped; // optimistic UI
      applyRatingColorToCell(cell, clamped); // ✅ instant color update

      const ok = await updateReviewFromCell(cell, "rating", clamped, prev);
      if (!ok) {
        cell.textContent = prev; // revert on failure
        applyRatingColorToCell(cell, prev); // ✅ revert color too
        closeActiveEditor(false);
        return;
      }

      closeActiveEditor(false);
    }

    input.addEventListener("keydown", (e) => {
      if (e.key === "Escape") { alreadySaved = true; closeActiveEditor(true); return; }
      if (e.key === "Enter") { e.preventDefault(); saveAndClose(); }
    });
    input.addEventListener("blur", () => saveAndClose());

    cell.textContent = "";
    cell.appendChild(input);
    input.focus();
    input.select();
    return;
  }

  openReviewModal({
    cell,
    reviewer,
    initialText: cell.textContent.trim(),
    titleForModal: cell.dataset.movieTitle || cell.dataset.tvShowTitle || "Unknown Title",
  });
}

/**
 * ✅ Key changes vs your current version:
 * - NO alert("Review saved!")
 * - NO hardReload()
 * - Returns true/false so caller can revert cell on failure
 * - ✅ Rating background color updates immediately (handled in caller + also reaffirmed on success below)
 */
async function updateReviewFromCell(cell, field, newValue, prevValueForRevert = "") {
  const token = localStorage.getItem("access_token");
  if (!token) { toast("You must be logged in to update a review.", 2200); return false; }

  const reviewType = (cell.getAttribute("data-review-type") || cell.dataset.reviewType || "movie").toLowerCase(); // movie|tv
  const reviewer   = (cell.dataset.reviewer || "").toLowerCase();
  const itemId     = cell.dataset.id;
  const coupleSlug = cell.dataset.coupleSlug || "";
  const targetType = normalizeTargetType(cell.getAttribute("data-target-type") || cell.dataset.targetType || "show");

  const existingReviewId = (cell.dataset.reviewId || "").trim(); // data-review-id="123"

  if (!itemId || !reviewer) {
    console.error("Missing itemId/reviewer", { itemId, reviewer });
    toast("Missing required data on this cell.", 2200);
    return false;
  }
  if (reviewer !== getCurrentUser()) {
    toast("You can only edit your own cell.", 2200);
    return false;
  }

  let url, method;
  const payload = {};

  // Decide PATCH vs POST
  if (existingReviewId) {
    method = "PATCH";
    url = reviewType === "tv"
      ? `https://movieclubdatabase.onrender.com/api/tv-reviews/${existingReviewId}/`
      : `https://movieclubdatabase.onrender.com/api/reviews/${existingReviewId}/`;

    payload[field] = (field === "rating")
      ? (newValue === "" ? null : parseFloat(newValue))
      : newValue;

    // ✅ TV PATCH: include exactly one FK target
    if (reviewType === "tv") {
      payload.target_type = targetType;

      if (targetType === "show") payload.tv_show_type = parseInt(itemId);
      if (targetType === "season") payload.tv_season_type = parseInt(itemId);
      if (targetType === "episode") payload.tv_episode_type = parseInt(itemId);
    }
  } else {
    method = "POST";
    url = reviewType === "tv"
      ? `https://movieclubdatabase.onrender.com/api/tv-reviews/`
      : `https://movieclubdatabase.onrender.com/api/reviews/`;

    if (reviewType === "tv") {
      payload.reviewer = reviewer;
      payload.target_type = targetType;
      if (coupleSlug) payload.couple_slug = coupleSlug;

      if (targetType === "show") payload.tv_show_type = parseInt(itemId);
      if (targetType === "season") payload.tv_season_type = parseInt(itemId);
      if (targetType === "episode") payload.tv_episode_type = parseInt(itemId);
    } else {
      payload.movie = parseInt(itemId);
      payload.reviewer = reviewer;
    }

    // set fields
    if (field === "rating") {
      payload.rating = (newValue === "" ? null : parseFloat(newValue));
      payload.rating_justification = "";
    } else {
      payload.rating_justification = newValue;
      payload.rating = null;
    }
  }

  console.log("Sending review request", { method, url, payload });

  toast("Saving…", 1200);

  try {
    const res = await fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer " + token,
      },
      body: JSON.stringify(payload),
    });

    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (_) {}

    if (!res.ok) {
      console.error("Save failed", { status: res.status, data, text });
      toast("Error saving — reverted.", 2500);
      return false;
    }

    // If we POSTed, store the new review id on the cell so next edit PATCHes
    if (!existingReviewId && data && data.id) {
      cell.dataset.reviewId = String(data.id);
      cell.setAttribute("data-review-id", String(data.id));
    }

    // ✅ Re-apply rating color after successful save (covers edge cases)
    if (field === "rating") {
      applyRatingColorToCell(cell, newValue);
    }

    toast("Saved ✅", 1200);
    return true;

  } catch (err) {
    console.error("Network/JS error:", err);
    toast("Network error — reverted.", 2500);
    return false;
  }
}
