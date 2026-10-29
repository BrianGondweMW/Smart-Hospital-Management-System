const familyForm = document.getElementById("familyForm");
const messageBox = document.getElementById("message");

familyForm.addEventListener("submit", async function(event) {
    event.preventDefault();

    const patientId = document
        .getElementById("patientId").value.trim();

    const relatedPatientId = document
        .getElementById("relatedPatientId").value.trim();

    const relationshipType = document
        .getElementById("relationshipType").value;

    messageBox.replaceChildren();

    if (patientId === relatedPatientId) {
        showMessage(
            "A patient cannot be linked to themselves.",
            "error"
        );
        return;
    }

    const button = familyForm.querySelector(
        'button[type="submit"]'
    );

    button.disabled = true;
    button.textContent = "Linking...";

    try {
        const response = await fetch(
            "../api/patients/link_family.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    patient_id: patientId,
                    related_patient_id: relatedPatientId,
                    relationship_type: relationshipType
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Unable to link family member."
            );
        }

        showMessage(result.message, "success");
        familyForm.reset();

    } catch (error) {
        showMessage(error.message, "error");

    } finally {
        button.disabled = false;
        button.textContent = "Link Family Member";
    }
});

function showMessage(text, type) {
    messageBox.replaceChildren();

    const box = document.createElement("div");
    box.className = type;
    box.textContent = text;

    messageBox.appendChild(box);
}
