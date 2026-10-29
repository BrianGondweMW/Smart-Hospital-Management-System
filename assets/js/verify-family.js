const statusBox = document.getElementById("verificationStatus");
const pendingContainer = document.getElementById(
    "pendingRelationships"
);

async function loadPendingRelationships() {
    statusBox.textContent = "Loading pending relationships...";

    try {
        const response = await fetch(
            "../api/patients/pending_family.php",
            { cache: "no-store" }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "Unable to load relationships."
            );
        }

        pendingContainer.replaceChildren();

        if (data.relationships.length === 0) {
            statusBox.textContent =
                "No pending relationships found.";
            return;
        }

        statusBox.textContent =
            data.relationships.length +
            " relationship(s) awaiting verification.";

        data.relationships.forEach(item => {
            const card = document.createElement("div");
            card.className = "patient-card";

            const heading = document.createElement("h3");
            heading.textContent =
                item.patient_first_name + " " +
                item.patient_last_name;

            const patient = document.createElement("p");
            patient.textContent =
                "Patient ID: " + item.patient_code;

            const related = document.createElement("p");
            related.textContent =
                "Linked Family Member: " +
                item.related_first_name + " " +
                item.related_last_name;

            const relatedId = document.createElement("p");
            relatedId.textContent =
                "Family Member ID: " + item.related_code;

            const relationship = document.createElement("p");
            relationship.textContent =
                "Relationship: " + item.relationship_type;

            const verification = document.createElement("p");
            verification.textContent =
                "Verification Status: Pending";

            const date = document.createElement("p");
            date.textContent =
                "Created: " + item.created_at;

           const notes = document.createElement("textarea");
notes.placeholder = "Enter verification notes...";
notes.rows = 3;
notes.maxLength = 2000;
notes.required = true;
notes.style.width = "100%";
notes.style.padding = "12px";
notes.style.marginTop = "12px";

const approveBtn = document.createElement("button");
approveBtn.type = "button";
approveBtn.textContent = "Approve";
approveBtn.style.background = "#16834a";

const rejectBtn = document.createElement("button");
rejectBtn.type = "button";
rejectBtn.textContent = "Reject";
rejectBtn.style.background = "#c62828";

approveBtn.addEventListener("click", () => {
    submitVerification(
        item.relationship_id,
        "Verified",
        notes,
        approveBtn,
        rejectBtn
    );
});

rejectBtn.addEventListener("click", () => {
    submitVerification(
        item.relationship_id,
        "Rejected",
        notes,
        approveBtn,
        rejectBtn
    );
});

approveBtn.addEventListener("click", () => {
    submitVerification(
        item.relationship_id,
        "Verified",
        notes,
        approveBtn,
        rejectBtn
    );
});

rejectBtn.addEventListener("click", () => {
    submitVerification(
        item.relationship_id,
        "Rejected",
        notes,
        approveBtn,
        rejectBtn
    );
});


card.append(
    heading,
    patient,
    related,
    relatedId,
    relationship,
    verification,
    date,
    notes,
    approveBtn,
    rejectBtn
);

            pendingContainer.appendChild(card);
        });

    } catch (error) {
        statusBox.textContent = error.message;
        statusBox.classList.add("error");
    }
}

loadPendingRelationships();


async function submitVerification(
    relationshipId,
    decision,
    notes,
    approveBtn,
    rejectBtn
) {
    const reason = notes.value.trim();

    if (!reason) {
        alert("Please enter verification notes.");
        notes.focus();
        return;
    }

    const confirmed = confirm(
        `Are you sure you want to mark this relationship as ${decision}?`
    );

    if (!confirmed) return;

    approveBtn.disabled = true;
    rejectBtn.disabled = true;

    try {
        const response = await fetch(
            "../api/patients/verify_relationship.php",
            {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    relationship_id: relationshipId,
                    decision: decision,
                    notes: reason
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Verification failed."
            );
        }

        alert(result.message);
        await loadPendingRelationships();

    } catch (error) {
        alert(error.message);

    } finally {
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
    }
}
