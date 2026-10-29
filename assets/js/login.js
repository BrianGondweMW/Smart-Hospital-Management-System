const loginForm = document.getElementById("loginForm");
const loginBtn = document.getElementById("loginBtn");
const loginMessage = document.getElementById("loginMessage");

loginForm.addEventListener("submit", async function(event) {
    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    loginMessage.replaceChildren();
    loginBtn.disabled = true;
    loginBtn.textContent = "Signing in...";

    try {
        const response = await fetch(
            "../api/auth/login.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "same-origin",
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            }
        );

        const contentType =
            response.headers.get("content-type") || "";

        if (!contentType.includes("application/json")) {
            throw new Error(
                "The server did not return JSON. " +
                "Check the login API path."
            );
        }

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Login failed."
            );
        }

        showLoginMessage("Login successful!", "success");

        window.location.href = "../dashboard/index.php";

    } catch (error) {
        showLoginMessage(error.message, "error");

    } finally {
        loginBtn.disabled = false;
        loginBtn.textContent = "Sign In";
    }
});

function showLoginMessage(text, type) {
    loginMessage.replaceChildren();

    const box = document.createElement("div");
    box.className = type;
    box.textContent = text;

    loginMessage.appendChild(box);
}
