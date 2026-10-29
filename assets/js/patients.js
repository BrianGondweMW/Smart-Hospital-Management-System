
const form = document.getElementById("patientForm");
const messageBox = document.getElementById("message");
const submitBtn = document.getElementById("submitBtn");
const dobInput = document.getElementById("date_of_birth");

dobInput.max = new Date().toLocaleDateString("en-CA");

function showMessage(text, type) {
    messageBox.replaceChildren();

    const box = document.createElement("div");
    box.className = type;
    box.textContent = text;

    messageBox.appendChild(box);
}

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    messageBox.replaceChildren();

    if (!form.reportValidity()) {
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = "Registering...";

    try {
        const formData = new FormData(form);

        const response = await fetch(
            "../api/patients/register.php",
            {
                method: "POST",
                body: formData
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Registration failed."
            );
        }

        showMessage(
            "Patient registered successfully! " +
            "Patient ID: " + result.patient_code,
            "success"
        );

        form.reset();

    } catch (error) {
        showMessage(error.message, "error");

    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = "Register Patient";
    }
});
