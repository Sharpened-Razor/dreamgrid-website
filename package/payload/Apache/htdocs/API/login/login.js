document.addEventListener('DOMContentLoaded', () => {

    // Target structural components
    const loginModal = document.getElementById('loginModal');
    const openLoginBtn = document.getElementById('openLoginBtn');
    const closeLoginBtn = document.getElementById('closeLoginBtn');
    const authForm = document.getElementById('authForm');

    // Open modal on click (button may be absent on pages that hide it once logged in)
    if (openLoginBtn) {
        openLoginBtn.addEventListener('click', () => {
            loginModal.classList.add('is-visible');
        });
    }

    // Close modal on clicking the X icon
    closeLoginBtn.addEventListener('click', () => {
        loginModal.classList.remove('is-visible');
    });

    // Close modal if user clicks anywhere outside the form box
    window.addEventListener('click', (event) => {
        if (event.target === loginModal) {
            loginModal.classList.remove('is-visible');
        }
    });

    // Handle data submission without reloading
    authForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const avatar = document.getElementById('avatar').value;
        const pass = document.getElementById('userPassword').value;

        avatarName = avatar;
        pwd = pass;

        command = 'Login';
        var params = { avatar: avatarName, password: pwd };

        $.ajax({
            url: "/API/login/login.php",
            type: "POST",
            data: params,
            success: function (response) {
                $("#result").text(command + ": " + response);

              //  console.log('Form Submitted:', {avatar, pass });

                // Close the popup window on success
                loginModal.classList.remove('is-visible');
                authForm.reset();

                if (response === "Success") {
                    $(document).trigger("dreamgrid:login", [avatarName]);
                }
            },
            error: function (xhr, status, error) {
                $("#result").text(command + " failed: " + status);
            }
        });
    });
});
