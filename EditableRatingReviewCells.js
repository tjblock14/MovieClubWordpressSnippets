/***************************************************************************************************
 * Movie Club — Inline/Modal Edit Script (single-file version)
 * - Ratings: inline number input (Enter/blur saves)
 * - Reviews: fullscreen modal textarea (Save/Cancel)
 * - Only one cell can be edited at a time
 * - Modal title shows reviewer + movie title
 ***************************************************************************************************/

/* ===== Global state ===== */
let activeCell = null;
let modalMeta  = null;          // { cell, reviewer, movieId }
let mcBackdrop = null;
let mcModal    = null;

document.addEventListener("DOMContentLoaded", () => {
  console.log("Review edit script (with modal) loaded");

  // Optional: only run if table exists
  // if (!document.querySelector('.tnt-table')) return;

  // Basic blocked-cell style
  const style = document.createElement("style");
  style.textContent = `.blocked-cell { cursor: not-allowed; }`;
  document.head.appendChild(style);

  // Modal + active-edit CSS
  const modalStyle = document.createElement("style");
  modalStyle.textContent = `
    .editing-cell { outline: 3px solid #4f46e5; outline-offset: -3px; }
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

  // Modal button handlers
  document.getElementById("mc-cancel").addEventListener("click", () => { closeReviewModal(); closeActiveEditor(true); });
  document.getElementById("mc-close").addEventListener("click",  () => { closeReviewModal(); closeActiveEditor(true); });
  document.getElementById("mc-save").addEventListener("click", async () => {
    if (!modalMeta) return;
    const text = document.getElementById("mc-textarea").value;
    modalMeta.cell.textContent = text;
    await updateReview(modalMeta.movieId, modalMeta.reviewer, "rating_justification", text);
    closeReviewModal();
    closeActiveEditor(false);
  });
  document.getElementById("mc-textarea").addEventListener("keydown", (e) => {
    if (e.key === "Escape") { e.preventDefault(); document.getElementById("mc-cancel").click(); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
      e.preventDefault(); document.getElementById("mc-save").click();
    }
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
});

/* === Helpers === */
function getCurrentUser() {
  return (localStorage.getItem('username') || '').toLowerCase();
}

function cellIsOwnedByUser(cell) {
  const reviewer = (cell.dataset.reviewer || '').toLowerCase();
  return reviewer === getCurrentUser();
}

async function findReviewId(movieId, reviewerUsername) {
  try {
    const response = await fetch("https://movieclubdatabase.onrender.com/api/reviews/");
    const data = await response.json();
    return data.find(r =>
      parseInt(r.movie) === parseInt(movieId) &&
      typeof r.reviewer === "string" &&
      r.reviewer.toLowerCase() === reviewerUsername.toLowerCase()
    )?.id || null;
  } catch (error) {
    console.error("Error fetching reviews:", error);
    return null;
  }
}

function makeCellEditable(cell) {
  if (!cellIsOwnedByUser(cell)) return;

  if (activeCell && activeCell !== cell) closeActiveEditor(true);
  if (activeCell === cell) return;
  activeCell = cell;
  cell.classList.add("editing-cell");

  const reviewer = cell.dataset.reviewer;
  const movieId = cell.dataset.id;
  const field = cell.classList.contains("rating-cell") ? "rating" : "rating_justification";

  if (field === "rating") {
    const currentValue = cell.textContent.trim();
    const input = document.createElement("input");
    input.type = "number";
    input.step = "0.1";
    input.min = "0";
    input.max = "10";
    input.value = currentValue || "";
    input.style.width = "70px";
    input.style.textAlign = "center";
    input.style.fontWeight = "700";

    input.addEventListener("keydown", (e) => {
      if (e.key === "Escape") return closeActiveEditor(true);
      if (e.key === "Enter") {
        const newValue = input.value.trim();
        const clamped = newValue === "" ? "" : Math.max(0, Math.min(10, parseFloat(newValue)));
        cell.textContent = clamped;
        updateReview(movieId, reviewer, "rating", clamped);
        closeActiveEditor(false);
      }
    });

    input.addEventListener("blur", () => {
      const newValue = input.value.trim();
      const clamped = newValue === "" ? "" : Math.max(0, Math.min(10, parseFloat(newValue)));
      cell.textContent = clamped;
      updateReview(movieId, reviewer, "rating", clamped);
      closeActiveEditor(false);
    });

    cell.textContent = "";
    cell.appendChild(input);
    input.focus();
    input.select();
    return;
  }

  // Modal for review text
  openReviewModal({
    cell,
    reviewer,
    movieId,
    initialText: cell.textContent.trim(),
    movieTitle: cell.dataset.movieTitle || "Unknown Movie"
  });
}

function openReviewModal({ cell, reviewer, movieId, initialText, movieTitle }) {
  modalMeta = { cell, reviewer, movieId };
  document.getElementById("mc-title").textContent =
    `Edit Review — ${reviewer} (${movieTitle})`;
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
  if (input && discard) activeCell.textContent = activeCell.textContent || "";
  activeCell = null;
}

function hardReload() {
  const url = new URL(window.location.href);
  url.searchParams.set("t", Date.now());
  window.location.replace(url.toString());
}

async function updateReview(movieId, reviewer, field, newValue) {
  const token = localStorage.getItem("access_token");
  if (!token) { alert("You must be logged in to update a review."); return; }
  if (!movieId || !reviewer) { console.error("Missing movieId or reviewer"); return; }

  const isOwner = reviewer.toLowerCase() === getCurrentUser();
  if (!isOwner) { alert("You can only edit your own cell."); return; }

  let reviewId = await findReviewId(movieId, reviewer);
  const method = reviewId ? "PATCH" : "POST";
  const url = reviewId
    ? `https://movieclubdatabase.onrender.com/api/reviews/${reviewId}/`
    : `https://movieclubdatabase.onrender.com/api/reviews/`;

  const payload = {};
  if (method === "PATCH") {
    payload[field] = field === "rating" ? parseFloat(newValue) : newValue;
  } else {
    payload["movie"] = parseInt(movieId);
    payload["reviewer"] = reviewer;
    if (field === "rating") {
      payload["rating"] = parseFloat(newValue);
      payload["rating_justification"] = "";
    } else {
      payload["rating_justification"] = newValue;
      payload["rating"] = "";
    }
  }

  try {
    const res = await fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer " + token,
      },
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    await res.json();
    alert("Review saved!");
    hardReload();
  } catch (err) {
    console.error("Update failed:", err);
    alert("Error: " + err.message);
  }
}
