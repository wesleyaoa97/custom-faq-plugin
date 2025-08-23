document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("faqSearch");
    const filterDropdown = document.getElementById("faqFilter");
    const faqItems = document.querySelectorAll(".faq-item");

    // FAQ Toggle Behavior (+/-)
    document.querySelectorAll(".faq-show").forEach((plus) => {
        plus.addEventListener("click", function () {
            // Close any already open FAQ
            document.querySelectorAll(".faq-item").forEach((item) => {
                item.classList.remove("active");
                item.querySelector(".faq-answer-outer").style.display = "none";
                item.querySelector(".faq-show").style.display = "block";
                item.querySelector(".faq-hide").style.display = "none";
            });

            // Open selected FAQ
            let faqItem = this.closest(".faq-item");
            faqItem.classList.add("active");
            faqItem.querySelector(".faq-answer-outer").style.display = "flex";
            faqItem.querySelector(".faq-show").style.display = "none";
            faqItem.querySelector(".faq-hide").style.display = "block";
        });
    });

    // Close FAQ when clicking on the - sign
    document.querySelectorAll(".faq-hide").forEach((minus) => {
        minus.addEventListener("click", function () {
            let faqItem = this.closest(".faq-item");
            faqItem.classList.remove("active");
            faqItem.querySelector(".faq-answer-outer").style.display = "none";
            faqItem.querySelector(".faq-show").style.display = "block";
            faqItem.querySelector(".faq-hide").style.display = "none";
        });
    });

    // Search Functionality
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            let searchValue = searchInput.value.replace(/[^a-zA-Z0-9 ]/g, "").toLowerCase();

            faqItems.forEach(item => {
                const question = item.querySelector(".faq-question").textContent.toLowerCase();
                item.style.display = question.includes(searchValue) ? "block" : "none";
            });
        });
    }

    // Category Filter Functionality
    if (filterDropdown) {
        filterDropdown.addEventListener("change", function () {
            const selectedCategory = filterDropdown.value;

            faqItems.forEach(item => {
                const itemCategories = item.dataset.category.split(" ");
                const shouldShow = selectedCategory === "all" || itemCategories.includes(selectedCategory);
                item.style.display = shouldShow ? "block" : "none";
            });
        });
    }

    // Animation on Click (Only on + and -)
    document.querySelectorAll('.faq-toggle').forEach((toggle) => {
        toggle.addEventListener('click', function () {
            toggle.classList.add('animated'); // Apply the animation
            
            // Remove the animation class after it plays (for repeat effect)
            // Needs to be worked on -> Currently Animation not working as desired
            setTimeout(() => {
                toggle.classList.remove('animated');
            }, 400);
        });
    });

});
