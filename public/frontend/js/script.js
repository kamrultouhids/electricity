const openMenu = document.getElementById("openMenu");
const closeMenu = document.getElementById("closeMenu");
const mobileMenu = document.getElementById("mobileMenu");

if (mobileMenu && openMenu) {
    function openMobileMenu(e) {
        if (e) e.preventDefault();
        mobileMenu.classList.remove("-translate-x-full");
        mobileMenu.classList.add("translate-x-0");
        document.body.classList.add("overflow-hidden");
        openMenu.setAttribute("aria-expanded", "true");
    }

    function closeMobileMenu(e) {
        if (e) e.preventDefault();
        mobileMenu.classList.add("-translate-x-full");
        mobileMenu.classList.remove("translate-x-0");
        document.body.classList.remove("overflow-hidden");
        openMenu.setAttribute("aria-expanded", "false");
        openMenu.focus();
    }

    openMenu.addEventListener("click", openMobileMenu);
    closeMenu?.addEventListener("click", closeMobileMenu);

    document.addEventListener("keydown", (ev) => {
        if (ev.key === "Escape") closeMobileMenu(ev);
    });

    mobileMenu.querySelectorAll("a").forEach((a) => {
        a.addEventListener("click", closeMobileMenu);
    });
}

mobileMenu.querySelectorAll("a").forEach((a) => {
    a.addEventListener("click", (e) => {
        closeMobileMenu();
        setTimeout(() => {
            window.location.href = a.href;
        }, 250);
    });
});

const track = document.getElementById("carouselTrack");
const slides = document.querySelectorAll(".carousel-slide");
const dots = document.querySelectorAll(".carousel-dot");

let currentIndex = 0;

function updateCarousel() {
    const offset = currentIndex * 100;
    track.style.transform = `translateX(-${offset}%)`;

    dots.forEach((dot, idx) => {
        dot.classList.toggle("bg-[#3585BC]", idx === currentIndex);
        dot.classList.toggle("bg-gray-300", idx !== currentIndex);
    });
}

dots.forEach((dot) => {
    dot.addEventListener("click", (e) => {
        currentIndex = parseInt(e.target.dataset.index);
        updateCarousel();
    });
});

setInterval(() => {
    currentIndex = (currentIndex + 1) % slides.length;
    updateCarousel();
}, 5000);

updateCarousel();

function toggleAccordion(index) {
    const content = document.getElementById(`content-${index}`);
    const icon = document.getElementById(`icon-${index}`);

    const minusSVG = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-8 h-8 text-[#CAE5F7]">
        <path d="M3.75 7.25a.75.75 0 0 0 0 1.5h8.5a.75.75 0 0 0 0-1.5h-8.5Z" />
     </svg>`;

    const plusSVG = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-8 h-8 text-[#CAE5F7]">
          <path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z" />
      </svg>`;

    if (content.style.maxHeight && content.style.maxHeight !== "0px") {
        content.style.maxHeight = "0";
        icon.innerHTML = plusSVG;
    } else {
        content.style.maxHeight = content.scrollHeight + "px";
        icon.innerHTML = minusSVG;
    }
}
