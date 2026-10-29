
const searchForm = document.getElementById("searchForm");
const searchInput = document.getElementById("searchInput");
const searchButton = document.getElementById("searchBtn");
const results = document.getElementById("results");
const statusBox = document.getElementById("status");

searchForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const search = searchInput.value.trim();

    if (search.length < 2) {
        statusBox.textContent = "Enter at least 2 characters.";
        return;
    }

    results.replaceChildren();
    statusBox.textContent = "Searching...";
    statusBox.classList.remove("error");
    searchButton.disabled = true;

    try {
        const response = await fetch(
            "../api/patients/search.php?q=" +
            encodeURIComponent(search),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Search failed.");
        }

        if (data.patients.length === 0) {
            statusBox.textContent = "No matching patients found.";
            return;
        }

        statusBox.textContent =
            data.patients.length + " patient(s) found.";

        data.patients.forEach(patient => {
            const card = document.createElement("div");
            card.className = "patient-card";

            const name = document.createElement("h3");
            name.textContent =
                patient.first_name + " " + patient.last_name;

            const id = document.createElement("p");
            id.textContent = "Patient ID: " + patient.patient_code;

            const dob = document.createElement("p");
            dob.textContent =
                "Date of Birth: " +
                (patient.date_of_birth || "Not recorded");

            const link = document.createElement("a");
            link.className = "profile-link";
            link.textContent = "View Profile";
            link.href =
                "profile.html?id=" +
                encodeURIComponent(patient.patient_id);

            card.append(name, id, dob, link);
            results.appendChild(card);
        });

    } catch (error) {
        statusBox.textContent = error.message;
        statusBox.classList.add("error");

    } finally {
        searchButton.disabled = false;
    }
});
