document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("save-faq").addEventListener("click", function () {
        let question = document.getElementById("faq-question").value.trim();
        let answer = document.getElementById("faq-answer").value.trim();
        let category = document.getElementById("faq-category").value;
        let newCategory = document.getElementById("new-faq-category").value.trim();

        if (question === "" || answer === "") {
            alert("Please enter both a question and an answer.");
            return;
        }

        let formData = new FormData();
        formData.append("action", "custom_faq_add");
        formData.append("question", question);
        formData.append("answer", answer);
        formData.append("category", category);
        formData.append("new_category", newCategory);
        formData.append("security", customFaqAdmin.ajax_nonce);

        fetch(customFaqAdmin.ajax_url, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("FAQ added successfully!");
                location.reload();
            } else {
                alert("Error: " + data.message);
            }
        });
    });

    // Bind Event Listeners Dynamically for Edit & Delete
    function bindEditDeleteButtons() {
        document.querySelectorAll(".delete-faq").forEach(button => {
            button.addEventListener("click", function (e) {
                e.preventDefault();
                let faqId = this.getAttribute("data-id");

                if (!confirm("Are you sure you want to delete this FAQ?")) return;

                let formData = new FormData();
                formData.append("action", "custom_faq_delete");
                formData.append("faq_id", faqId);
                formData.append("security", customFaqAdmin.ajax_nonce);

                fetch(customFaqAdmin.ajax_url, {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("FAQ deleted successfully!");
                        location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            });
        });

        document.querySelectorAll(".edit-faq").forEach(button => {
            button.addEventListener("click", function () {
                let row = this.closest("tr");
                let faqId = row.getAttribute("data-id");
                let question = row.querySelector(".faq-question").textContent;
                let answer = row.querySelector(".faq-answer").textContent;
                let category = row.querySelector(".faq-category").textContent;

                let newQuestion = prompt("Edit Question:", question);
                if (newQuestion === null) return;

                let newAnswer = prompt("Edit Answer:", answer);
                if (newAnswer === null) return;

                // Fetch FAQ Categories for the dropdown
                fetch(customFaqAdmin.ajax_url + "?action=get_faq_categories")
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let categoryDropdown = "<select id='faq-edit-category'>";
                        data.categories.forEach(cat => {
                            let selected = cat.name === category ? "selected" : "";
                            categoryDropdown += `<option value="${cat.term_id}" ${selected}>${cat.name}</option>`;
                        });
                        categoryDropdown += "</select>";

                        let newCategory = prompt("Edit Category:\n" + categoryDropdown, category);
                        if (newCategory === null) return;

                        let formData = new FormData();
                        formData.append("action", "custom_faq_edit");
                        formData.append("faq_id", faqId);
                        formData.append("question", newQuestion);
                        formData.append("answer", newAnswer);
                        formData.append("category", newCategory);
                        formData.append("security", customFaqAdmin.ajax_nonce);

                        fetch(customFaqAdmin.ajax_url, {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("FAQ updated successfully!");
                                location.reload();
                            } else {
                                alert("Error: " + data.message);
                            }
                        });
                    }
                });
            });
        });
    }

    // Bind when page loads
    bindEditDeleteButtons();
});

