/**
 * KlinikPKP — Shared Navigation & UI Logic
 * Disperakim Provinsi Jawa Tengah
 *
 * Auto-injects: top bar, breadcrumb, form handling
 * Usage: Add <script src="shared.js"></script> before </body>
 *        Add data-page="pageName" to <body>
 */

(function () {
	"use strict";

	// ─── Page Map: defines label, parent, and role for breadcrumb ───
	const PAGE_MAP = {
		index: { label: "Home", parent: null, role: null },
		umum: { label: "Menu Umum", parent: "index", role: "umum" },
		pengembang: {
			label: "Menu Pengembang",
			parent: "index",
			role: "pengembang",
		},
		kemitraan: { label: "KKN & Magang", parent: "index", role: "kemitraan" },
		listkabupaten: {
			label: "Kabupaten / Kota",
			parent: "index",
			role: "kabupaten",
		},
		// -- Umum children --
		housing_carrier: { label: "Housing Carrier", parent: "umum", role: "umum" },
		"program_perumaha..": {
			label: "Program Perumahan",
			parent: "housing_carrier",
			role: "umum",
		},
		inforumah: { label: "Info Rumah", parent: "umum", role: "umum" },
		srpp: { label: "SRPP", parent: "umum", role: "umum" },
		aduan: { label: "Layanan Aduan", parent: "umum", role: "umum" },
		forum: { label: "Forum PKP", parent: "umum", role: "umum" },
		// -- Pengembang children --
		sertifikat: {
			label: "Sertifikasi",
			parent: "pengembang",
			role: "pengembang",
		},
		publikasi: {
			label: "Daftar Publikasi",
			parent: "pengembang",
			role: "pengembang",
		},
		inputpublikasi: {
			label: "Input Publikasi",
			parent: "publikasi",
			role: "pengembang",
		},
		// -- Kemitraan children --
		kkn: { label: "KKN Tematik", parent: "kemitraan", role: "kemitraan" },
		magang: {
			label: "Pendaftaran Magang",
			parent: "kemitraan",
			role: "kemitraan",
		},
		ketentuan: { label: "Ketentuan", parent: "kemitraan", role: "kemitraan" },
		// -- Kabupaten children --
		kabupaten: {
			label: "Input Data Intervensi",
			parent: "listkabupaten",
			role: "kabupaten",
		},
		// -- Housing Carrier children --
		syarat: {
			label: "Syarat Program",
			parent: "program_perumaha..",
			role: "umum",
		},
		syarat2: { label: "Tabel Pembiayaan", parent: "syarat", role: "umum" },
	};

	// ─── Role badge colors ───
	const ROLE_COLORS = {
		umum: {
			bg: "rgba(255,193,7,0.2)",
			border: "rgba(255,193,7,0.4)",
			text: "#ffc107",
			label: "Umum",
		},
		pengembang: {
			bg: "rgba(111,66,193,0.2)",
			border: "rgba(111,66,193,0.4)",
			text: "#a78bfa",
			label: "Pengembang",
		},
		kemitraan: {
			bg: "rgba(13,202,240,0.2)",
			border: "rgba(13,202,240,0.4)",
			text: "#0dcaf0",
			label: "KKN & Magang",
		},
		kabupaten: {
			bg: "rgba(25,135,84,0.2)",
			border: "rgba(25,135,84,0.4)",
			text: "#198754",
			label: "Kabupaten",
		},
	};

	// ─── Detect current page from data-page attribute or filename ───
	function getCurrentPage() {
		const dataPage = document.body.getAttribute("data-page");
		if (dataPage) return dataPage;

		const path = window.location.pathname;
		const filename = path.split("/").pop().replace(".html", "");
		return filename || "index";
	}

	// ─── Get parent URL ───
	function getPageUrl(pageKey) {
		if (pageKey === "index") return "index.html";
		return pageKey + ".html";
	}

	// ─── Build breadcrumb chain ───
	function buildBreadcrumb(pageKey) {
		const chain = [];
		let current = pageKey;
		let safety = 0;
		while (current && safety < 10) {
			const info = PAGE_MAP[current];
			if (!info) break;
			chain.unshift({ key: current, label: info.label });
			current = info.parent;
			safety++;
		}
		return chain;
	}

	// ─── Inject Top Bar ───
	function injectTopBar() {
		const pageKey = getCurrentPage();
		const pageInfo = PAGE_MAP[pageKey];

		// Don't inject on index (landing page)
		if (pageKey === "index" || !pageInfo) return;

		const parentKey = pageInfo.parent;
		const parentUrl = parentKey ? getPageUrl(parentKey) : "index.html";
		const role = pageInfo.role;
		const roleInfo = ROLE_COLORS[role];

		// --- Top Bar ---
		const topbar = document.createElement("div");
		topbar.className = "kpkp-topbar";
		topbar.innerHTML = `
      <a href="${parentUrl}" class="kpkp-topbar-back" aria-label="Kembali">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
          <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
        </svg>
        Kembali
      </a>
      <div class="kpkp-topbar-brand">
        <span class="kpkp-topbar-title">KlinikPKP</span>
        <span class="kpkp-topbar-subtitle">Disperakim Jateng</span>
      </div>
      <a href="index.html" class="kpkp-topbar-home" aria-label="Home" title="Kembali ke Beranda">
        <i class="bi bi-house-door-fill"></i>
      </a>
    `;

		// --- Breadcrumb ---
		const breadcrumb = document.createElement("div");
		breadcrumb.className = "kpkp-breadcrumb";

		const chain = buildBreadcrumb(pageKey);
		const bcParts = [];

		// Add Home icon
		bcParts.push(
			'<a href="index.html"><i class="bi bi-house-door-fill" style="margin-right:4px;"></i>Home</a>',
		);

		chain.forEach((item, idx) => {
			bcParts.push('<span class="kpkp-bc-sep">›</span>');
			if (idx === chain.length - 1) {
				// Current page (no link)
				bcParts.push(`<span class="kpkp-bc-current">${item.label}</span>`);
			} else {
				bcParts.push(`<a href="${getPageUrl(item.key)}">${item.label}</a>`);
			}
		});

		// Add role badge if applicable
		if (roleInfo) {
			bcParts.push(
				`<span style="margin-left:auto; padding:2px 10px; border-radius:6px; font-weight:700; font-size:0.7rem; background:${roleInfo.bg}; border:1px solid ${roleInfo.border}; color:${roleInfo.text}; text-transform:uppercase; letter-spacing:0.5px;">${roleInfo.label}</span>`,
			);
		}

		breadcrumb.innerHTML = bcParts.join("");

		// --- Insert into DOM ---
		document.body.prepend(breadcrumb);
		document.body.prepend(topbar);
		document.body.classList.add("kpkp-has-nav");
	}

	// ─── Form Handling: Intercept all forms and show success toast ───
	function setupFormHandling() {
		const forms = document.querySelectorAll("form");
		forms.forEach(function (form) {
			form.addEventListener("submit", function (e) {
				e.preventDefault();

				// Basic validation
				const requiredFields = form.querySelectorAll("[required]");
				let valid = true;
				requiredFields.forEach(function (field) {
					if (!field.value.trim()) {
						valid = false;
						field.style.borderColor = "#dc3545";
						field.style.boxShadow = "0 0 0 3px rgba(220,53,69,0.25)";
					} else {
						field.style.borderColor = "";
						field.style.boxShadow = "";
					}
				});

				if (!valid) {
					showToast("Mohon lengkapi semua field yang wajib diisi.", "error");
					return;
				}

				// Simulate success
				showToast("Data berhasil dikirim!", "success");

				// Reset form after delay
				setTimeout(function () {
					form.reset();
				}, 1500);
			});
		});
	}

	// ─── Toast Notification ───
	function showToast(message, type) {
		// Remove existing toast
		const existing = document.querySelector(".kpkp-toast");
		if (existing) existing.remove();

		const toast = document.createElement("div");
		toast.className = "kpkp-toast kpkp-toast-" + (type || "success");
		toast.textContent = message;
		document.body.appendChild(toast);

		// Trigger animation
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				toast.classList.add("kpkp-toast-show");
			});
		});

		// Auto-hide
		setTimeout(function () {
			toast.classList.remove("kpkp-toast-show");
			setTimeout(function () {
				toast.remove();
			}, 400);
		}, 3000);
	}

	// ─── Fix: Remove [cite: XX] artifacts from page ───
	function removeCiteArtifacts() {
		const walker = document.createTreeWalker(
			document.body,
			NodeFilter.SHOW_TEXT,
			null,
			false,
		);

		const nodesToClean = [];
		while (walker.nextNode()) {
			const node = walker.currentNode;
			if (/\[cite:\s*\d+\]/.test(node.textContent)) {
				nodesToClean.push(node);
			}
		}

		nodesToClean.forEach(function (node) {
			const cleaned = node.textContent.replace(/\[cite:\s*\d+\]/g, "").trim();
			if (cleaned) {
				node.textContent = cleaned;
			} else {
				node.remove();
			}
		});
	}

	// ─── Initialize on DOM ready ───
	function init() {
		removeCiteArtifacts();
		injectTopBar();
		setupFormHandling();
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
