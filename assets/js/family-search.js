const searchForm = document.getElementById("familySearchForm");
const searchInput = document.getElementById("familySearchInput");
const searchBtn = document.getElementById("familySearchBtn");
const statusBox = document.getElementById("searchStatus");
const parentResults = document.getElementById("parentResults");
const familyResults = document.getElementById("linkedFamilyResults");

searchForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const query = searchInput.value.trim();

    parentResults.replaceChildren();
    familyResults.textContent = "No family member selected.";

    if (query.length < 2) {
        statusBox.textContent = "Enter at least 2 characters.";
        return;
    }

    statusBox.textContent = "Searching...";
    searchBtn.disabled = true;

    try {
        const response = await fetch(
            "../api/patients/search.php?q=" +
            encodeURIComponent(query),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Search failed.");
        }

        parentResults.replaceChildren();

        if (data.patients.length === 0) {
            statusBox.textContent = "No matching patients found.";
            return;
        }

        statusBox.textContent =
            data.patients.length + " possible match(es) found.";

        data.patients.forEach(patient => {
            const card = document.createElement("div");
            card.className = "patient-card";

            const name = document.createElement("h3");
            name.textContent =
                patient.first_name + " " + patient.last_name;

            const code = document.createElement("p");
            code.textContent = "Patient ID: " + patient.patient_code;

            const button = document.createElement("button");
            button.type = "button";
            button.textContent = "Find Linked Family";

            button.addEventListener("click", () => {
                loadLinkedFamily(patient.patient_id);
            });

            card.append(name, code, button);
            parentResults.appendChild(card);
        });

    } catch (error) {
        statusBox.textContent = error.message;

    } finally {
        searchBtn.disabled = false;
    }
});

async function loadLinkedFamily(patientId) {
    familyResults.textContent = "Loading linked family members...";

    try {
        const response = await fetch(
            "../api/patients/family_members.php?id=" +
            encodeURIComponent(patientId),
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Unable to load family."
            );
        }

        familyResults.replaceChildren();

        const verifiedMembers = data.family_members.filter(
            member => member.verified === true
        );

        if (verifiedMembers.length === 0) {
            familyResults.textContent =
                "No verified family relationships found.";
            return;
        }

        verifiedMembers.forEach(member => {
            const card = document.createElement("div");
            card.className = "patient-card";

            const name = document.createElement("h3");
            name.textContent =
                member.first_name + " " + member.last_name;

            const relationship = document.createElement("p");
            relationship.textContent =
                "Relationship: " + member.relationship_type;

            const status = document.createElement("p");
            status.textContent = "Relationship: Verified";

            const info = document.createElement("p");
            info.textContent =
                "Identity must be confirmed before record access.";

            card.append(name, relationship, status, info);
            familyResults.appendChild(card);
        });

    } catch (error) {
        familyResults.textContent = error.message;
    }
}
