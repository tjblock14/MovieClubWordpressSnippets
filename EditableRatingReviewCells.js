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
    .blocked-cell { }
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
          <div>
            <div id="mc-title">Rating / Review</div>
            <div id="mc-subtitle" style="font-size:12px; opacity:.75; margin-top:4px;"></div>
          </div>
          <button id="mc-close" class="mc-btn">Close</button>
        </div>

        <div class="mc-bd">

          <!-- ================= VIEW ONLY (non-owner) ================= -->
          <div class="mc-viewonly" style="display:none;">
            <div style="margin-bottom:14px;">
              <div style="font-size:12px; opacity:.8; margin-bottom:6px;">Rating</div>
              <div id="mc-view-rating"
                  style="font-size:20px; font-weight:700;"></div>
            </div>

            <div>
              <div style="font-size:12px; opacity:.8; margin-bottom:6px;">Justification</div>
              <div id="mc-view-review"
                  style="
                    white-space:pre-wrap;
                    padding:14px;
                    border-radius:12px;
                    border:1px solid rgba(255,255,255,.12);
                    background:rgba(255,255,255,.03);
                    min-height:160px;
                  ">
              </div>
            </div>
          </div>

          <!-- ================= EDITABLE (owner only) ================= -->
          <div class="mc-editable" style="display:none;">
            <label style="display:block; font-size:12px; opacity:.8; margin-bottom:6px;">
              Rating (0–10)
            </label>
            <input id="mc-rating" type="number" min="0" max="10" step="0.1"
                  style="width:120px; padding:10px 12px; border-radius:10px; border:1px solid #334155;
                          background:#0b1220; color:#e2e8f0; font-weight:700; text-align:center;" />

            <label style="display:block; font-size:12px; opacity:.8; margin:14px 0 6px;">
              Justification
            </label>
            <textarea id="mc-textarea" class="mc-txt"
                      placeholder="Type your justification..."></textarea>

            <div id="mc-hint" style="margin-top:10px; font-size:12px; opacity:.75;"></div>
          </div>

        </div>

        <div class="mc-ft">
          <button id="mc-cancel" class="mc-btn">Close</button>
          <button id="mc-save" class="mc-btn primary">Save</button>
        </div>
      </div>
    `;

  document.body.append(mcBackdrop, mcModal);

  document.getElementById("mc-cancel").addEventListener("click", () => { closeReviewModal(); });
  document.getElementById("mc-close").addEventListener("click",  () => { closeReviewModal(); });

  document.getElementById("mc-save").addEventListener("click", async () => {
    if (!modalMeta) return;

    const { cell, isOwner } = modalMeta;
    if (!isOwner) return;

    const prevRating = (cell.dataset.rating ?? cell.textContent ?? "").trim();
    const prevReview = (cell.dataset.review ?? "").trim();
    const prevColor  = (cell.dataset.ratingColor ?? "").trim();

    const ratingRaw = (document.getElementById("mc-rating").value || "").trim();
    const ratingNum = parseFloat(ratingRaw);

    if (!Number.isFinite(ratingNum) || ratingNum < 0 || ratingNum > 10) {
      toast("Rating must be between 0 and 10.", 2200);
      return;
    }

    const justification = (document.getElementById("mc-textarea").value || "").trim();

    // optimistic UI update
    cell.dataset.rating = String(ratingNum);
    cell.dataset.review = justification;
    cell.textContent = String(ratingNum);

    // IMPORTANT: keep color synced + update data-rating-color
    applyRatingColorToCell(cell, String(ratingNum));

    const ok = await saveRatingAndJustification(cell, ratingNum, justification);

    if (!ok) {
      // revert
      cell.dataset.rating = prevRating;
      cell.dataset.review = prevReview;
      cell.dataset.ratingColor = prevColor;
      cell.textContent = prevRating;
      applyRatingColorToCell(cell, prevRating);
      return;
    }

    closeReviewModal();
  });

  document.getElementById("mc-textarea").addEventListener("keydown", (e) => {
    if (e.key === "Escape") { e.preventDefault(); document.getElementById("mc-cancel").click(); }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") { e.preventDefault(); document.getElementById("mc-save").click(); }
  });

  // Attach click handlers to all rating cells (modal decides owner vs not)
  document.querySelectorAll(".rating-cell").forEach((cell) => {
    const reviewer = (cell.dataset.reviewer || "").toLowerCase();
    const isOwner = reviewer && reviewer === getCurrentUser();

    if (!isOwner) cell.classList.add("blocked-cell");
    else cell.classList.remove("blocked-cell");

    cell.addEventListener("click", () => openRatingReviewModalFromCell(cell));
  });

  // Apply colors on initial load:
  // - If PHP provided data-rating-color, we keep it.
  // - Otherwise we compute it.
  document.querySelectorAll(".rating-cell").forEach((cell) => {
    const v = (cell.dataset.rating ?? cell.textContent ?? "").trim();
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

/* === Rating color helpers === */
function ratingToBg(r) {
  if (r === "" || r === null || typeof r === "undefined") return "";
  const n = Number(r);
  if (n >= 9.5)                  return "#0096FF"; // Blue
        else if (n >= 8.5)             return "#3C8D40"; // dark green
        else if (n >= 7.0)             return "#5F9F61"; // light green
        else if (n >= 5.0)             return "#F3EAA3"; // yellow
        else if (n >= 3.5)             return "#EFB45D"; // orange
        else                                 return "#AC2727"; // dark red
}

function applyRatingColorToCell(cell, ratingValue) {
  // ✅ Prefer PHP-provided color if present
  const phpColor = (cell.dataset.ratingColor || "").trim();
  const computed = ratingToBg(ratingValue);
  const bg = phpColor || computed;

  cell.style.backgroundColor = bg;

  // Keep dataset synced so modal can always read it
  if (bg) {
    cell.dataset.ratingColor = bg;
    cell.setAttribute("data-rating-color", bg);
  } else {
    delete cell.dataset.ratingColor;
    cell.removeAttribute("data-rating-color");
  }

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

function normalizeTargetType(tt) {
  const v = String(tt || "show").trim().toLowerCase();
  if (v === "show" || v === "season" || v === "episode") return v;
  return "show";
}

function closeReviewModal() {
  mcBackdrop.classList.remove("open");
  mcModal.classList.remove("open");
  modalMeta = null;
}

async function saveRatingAndJustification(cell, ratingNum, justification) {
  const token = localStorage.getItem("access_token");
  if (!token) { toast("You must be logged in to update a review.", 2200); return false; }

  const reviewType = (cell.getAttribute("data-review-type") || cell.dataset.reviewType || "movie").toLowerCase(); // movie|tv
  const reviewer   = (cell.dataset.reviewer || "").toLowerCase();
  const itemId     = cell.dataset.id;
  const coupleSlug = cell.dataset.coupleSlug || "";
  const targetType = normalizeTargetType(cell.getAttribute("data-target-type") || cell.dataset.targetType || "show");

  const existingReviewId = (cell.dataset.reviewId || "").trim();

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

  if (existingReviewId) {
    method = "PATCH";
    url = reviewType === "tv"
      ? `https://movieclubdatabase.onrender.com/api/tv-reviews/${existingReviewId}/`
      : `https://movieclubdatabase.onrender.com/api/reviews/${existingReviewId}/`;

    payload.rating = ratingNum;
    payload.rating_justification = justification;

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

    payload.rating = ratingNum;
    payload.rating_justification = justification;
  }

  console.log("Saving rating+justification", { method, url, payload });
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

    // if created, store the new review id on THIS cell
    if (!existingReviewId && data && data.id) {
      cell.dataset.reviewId = String(data.id);
      cell.setAttribute("data-review-id", String(data.id));
    }

    toast("Saved ✅", 1200);
    return true;

  } catch (err) {
    console.error("Network/JS error:", err);
    toast("Network error — reverted.", 2500);
    return false;
  }
}

function openRatingReviewModalFromCell(cell) {
  const reviewer = (cell.dataset.reviewer || "").toLowerCase();
  const currentUser = getCurrentUser();
  const isOwner = reviewer && currentUser && reviewer === currentUser;

  // Values from clicked rating cell
  const rating = (cell.dataset.rating ?? cell.textContent ?? "").trim();
  let review = (cell.dataset.review ?? "").trim();

  // Grab PHP-provided rating color (data-rating-color="...")
  const ratingColor = (cell.dataset.ratingColor || "").trim();

  // OPTIONAL fallback: if rating cell doesn't have review, grab it from same row .review-cell
  if (!review) {
    const row = cell.closest("tr");
    if (row) {
      const reviewCell = row.querySelector(`.review-cell[data-reviewer="${reviewer}"]`);
      if (reviewCell) review = (reviewCell.textContent || "").trim();
    }
  }

  // Modal sections/fields
  const viewOnlySection = mcModal.querySelector(".mc-viewonly");
  const editableSection = mcModal.querySelector(".mc-editable");

  const viewRating = mcModal.querySelector("#mc-view-rating");
  const viewReview = mcModal.querySelector("#mc-view-review");

  const ratingInput = mcModal.querySelector("#mc-rating");
  const reviewTextarea = mcModal.querySelector("#mc-textarea");
  const saveBtn = mcModal.querySelector("#mc-save");

  // Populate view-only
  if (viewRating) {
    viewRating.textContent = rating || "—";
    if (ratingColor) viewRating.style.color = ratingColor;
  }
  if (viewReview) viewReview.textContent = review || "—";

  // Populate editable inputs
  if (ratingInput) {
    ratingInput.value = rating || "";
    // ✅ This is the line you wanted: popout text becomes cell color
    if (ratingColor) ratingInput.style.color = ratingColor;
  }
  if (reviewTextarea) reviewTextarea.value = review || "";

  // Toggle mode
  if (isOwner) {
    if (editableSection) editableSection.style.display = "block";
    if (viewOnlySection) viewOnlySection.style.display = "none";
    if (saveBtn) saveBtn.style.display = "inline-block";
  } else {
    if (editableSection) editableSection.style.display = "none";
    if (viewOnlySection) viewOnlySection.style.display = "block";
    if (saveBtn) saveBtn.style.display = "none";
  }

  // Save modal meta for the Save handler
  modalMeta = { cell, isOwner };

  // Update subtitle
  const sub = mcModal.querySelector("#mc-subtitle");
  if (sub) {
    const rt = (cell.dataset.reviewType || cell.getAttribute("data-review-type") || "").toLowerCase();
    const tt = (cell.dataset.targetType || cell.getAttribute("data-target-type") || "").toLowerCase();
    sub.textContent = `Reviewer: ${reviewer || "?"}${tt ? ` • Target: ${tt}` : ""}${rt ? ` • Type: ${rt}` : ""}`;
  }

  // OPEN modal
  mcBackdrop.classList.add("open");
  mcModal.classList.add("open");
}
