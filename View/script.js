function togglePassword(event) {
    event.preventDefault();
    var passwordInput = document.getElementById("password");
    var type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    
    // Toggle the button's text or icon here if needed
}
