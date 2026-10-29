
const profileContainer = document.getElementById("profile");
const statusElement = document.getElementById("status");

const params = new URLSearchParams(window.location.search);
const patientId = params.get("id");

function addInfo(label, value) {
    const wrapper = document.createElement("div");
    wrapper.className = "info-item";

    const title = document.createElement("span");
    title.textContent = label;

    const content = document.createElement("strong");
    content.textContent = value || "Not recorded";

    wrapper.append(title, content);
    profileContainer.appendChild(wrapper);
}

async function loadPatient() {
    if (!patientId || !/^[1-9]\d*$/.test(patientId)) {
        statusElement.textContent = "Invalid patient ID.";
        return;
    }

    try {
        const response = await fetch(
            "../api/patients/profile.php?id=" +
            encodeURIComponent(patientId),
            { cache: "no-store" }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Unable to load patient."
            );
        }

        const patient = result.patient;

        profileContainer.replaceChildren();

        addInfo("Patient ID", patient.patient_code);
        addInfo(
            "Full Name",
            patient.first_name + " " + patient.last_name
        );
        addInfo("Date of Birth", patient.date_of_birth);
        addInfo("Gender", patient.gender);
        addInfo("Phone Number", patient.phone);
        addInfo("Address", patient.address);
        const [date, time] = patient.created_at.split(" ");

addInfo("Registration Date", date);
addInfo("Registration Time", time);
        statusElement.textContent = "Patient record loaded.";

    } catch (error) {
        statusElement.textContent = error.message;
        statusElement.classList.add("error");
    }
}

loadPatient();
async function loadFamilyMembers() {
    const familyContainer =
        document.getElementById("familyMembers");

    if (!patientId || !/^[1-9]\d*$/.test(patientId)) {
        familyContainer.textContent = "Invalid patient ID.";
        return;
    }

    try {
        const response = await fetch(
            "../api/patients/family_members.php?id=" +
            encodeURIComponent(patientId)
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Unable to load family members."
            );
        }

        familyContainer.replaceChildren();

        if (data.family_members.length === 0) {
            familyContainer.textContent =
                "No family members linked yet.";
            return;
        }

        data.family_members.forEach(member => {
            const card = document.createElement("div");
            card.className = "patient-card";

            const name = document.createElement("h3");
            name.textContent =
                member.first_name + " " + member.last_name;

            const code = document.createElement("p");
            code.textContent =
                "Patient ID: " + member.patient_code;

            const relationship = document.createElement("p");
            relationship.textContent =
                "Relationship: " + member.relationship_type;

            const status = document.createElement("p");
            status.textContent = member.verified
                ? "Verification: Verified"
                : "Verification: Pending";

            const link = document.createElement("a");
            link.className = "profile-link";
            link.textContent = "View Profile";
            link.href =
                "profile.html?id=" +
                encodeURIComponent(member.patient_id);

            card.append(
                name, code, relationship, status, link
            );

            familyContainer.appendChild(card);
        });

    } catch (error) {
        familyContainer.textContent = error.message;
    }
}

loadFamilyMembers();
