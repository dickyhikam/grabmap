// Enhanced loading functions
function showLoading(message = null, submessage = null) {
    const loadingElement = document.getElementById("loading");
    const loadDetails = document.getElementById("load-details");

    // Reset loading state
    loadingElement.className = "loading";
    loadingElement.style.display = "flex";

    // Update messages if provided
    if (message) {
        const loadingText = loadingElement.querySelector(".loading-text");
        loadingText.innerHTML = message + '<span class="loading-dots"></span>';
    }

    if (submessage) {
        const loadingSubtext = loadingElement.querySelector(".loading-subtext");
        loadingSubtext.textContent = submessage;
    }

    // Clear details
    loadDetails.innerHTML = "";
    loadDetails.classList.remove("show");
}

function hideLoading() {
    const loadingElement = document.getElementById("loading");
    loadingElement.classList.add("hide");

    setTimeout(() => {
        loadingElement.style.display = "none";
        loadingElement.classList.remove("hide");
    }, 300);
}

function showLoadDetails(details) {
    const loadDetails = document.getElementById("load-details");
    loadDetails.innerHTML = details;
    loadDetails.classList.add("show");
}

function setLoadingError(message) {
    const loadingElement = document.getElementById("loading");
    loadingElement.classList.add("error");

    const loadingText = loadingElement.querySelector(".loading-text");
    loadingText.innerHTML = message;

    const loadingSubtext = loadingElement.querySelector(".loading-subtext");
    loadingSubtext.textContent = "Please try again";

    // Hide spinner and progress bar
    loadingElement.querySelector(".loading-spinner").style.display = "none";
    loadingElement.querySelector(".loading-progress").style.display = "none";
}

function setLoadingSuccess(message) {
    const loadingElement = document.getElementById("loading");
    loadingElement.classList.add("success");

    const loadingText = loadingElement.querySelector(".loading-text");
    loadingText.innerHTML = message;

    const loadingSubtext = loadingElement.querySelector(".loading-subtext");
    loadingSubtext.textContent = "Ready to explore!";

    // Auto hide after success
    setTimeout(() => {
        hideLoading();
    }, 1500);
}
