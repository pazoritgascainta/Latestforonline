 // Function to format date as "Month Day, Year"
    function formatDate(date) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }

    // Set current date in "Month Day, Year" format
    document.getElementById('current-date').textContent = formatDate(new Date());

    // Function to get current month and year
    function getCurrentMonth() {
        const today = new Date();
        const options = { month: 'long', year: 'numeric' }; // Display full month name and year
        return today.toLocaleDateString(undefined, options); // E.g., "September 2024"
    }

    // Get the anchor tag by ID and append the current month
    document.getElementById("billing-link").textContent += getCurrentMonth();

    // // Refresh button functionality (You can add actual refresh logic here)
    // document.getElementById("refresh-button").addEventListener("click", function() {
    //     alert("Refresh Rates clicked!");
    // });

    // File upload handler
    document.getElementById("upload-button").addEventListener("click", function() {
        const fileInput = document.getElementById("upload-file");
        if (fileInput.files.length === 0) {
            alert("No file selected!");
        } else {
            alert("File uploaded: " + fileInput.files[0].name);
        }
    });
            // Get elements
            const paymentModal = document.getElementById("paymentTermsModal");
            const payHereLink = document.getElementById("pay-here-link");
            const closePaymentModal = document.getElementById("closePaymentModal");
            const paymentAcceptBtn = document.getElementById("paymentAcceptBtn");
            const paymentCheckbox1 = document.getElementById("paymentCheckbox1");
            const paymentCheckbox2 = document.getElementById("paymentCheckbox2");
    
            // Function to check if user has already accepted terms
            function hasUserAcceptedPaymentTerms() {
                return localStorage.getItem("paymentTermsAccepted") === "true";
            }
    
            // Open the modal only if user hasn't accepted terms
            payHereLink.addEventListener("click", function (event) {
                if (hasUserAcceptedPaymentTerms()) {
                    // Proceed to payment page if terms already accepted
                    window.open("payment_page.php", "_blank");
                } else {
                    event.preventDefault(); // Prevent link default behavior
                    paymentModal.style.display = "block";
                }
            });
    
            // Close the modal
            closePaymentModal.addEventListener("click", function () {
                paymentModal.style.display = "none";
            });
    
            // Enable the Accept button only when both checkboxes are checked
            function togglePaymentAcceptButton() {
                paymentAcceptBtn.disabled = !(paymentCheckbox1.checked && paymentCheckbox2.checked);
            }
    
            paymentCheckbox1.addEventListener("change", togglePaymentAcceptButton);
            paymentCheckbox2.addEventListener("change", togglePaymentAcceptButton);
    
            // Accept and Proceed button
            paymentAcceptBtn.addEventListener("click", function () {
                // Save user's acknowledgment in localStorage
                localStorage.setItem("paymentTermsAccepted", "true");
                // Proceed to payment page
                window.open("payment_page.php", "_blank");
            });
    
            // Close the modal when clicking outside of it
            window.onclick = function (event) {
                if (event.target == paymentModal) {
                    paymentModal.style.display = "none";
                }
            };