document.addEventListener("DOMContentLoaded", () => {

    /*
    |--------------------------------------------------------------------------
    | LOGIN PAGE
    |--------------------------------------------------------------------------
    */

    const loginPage =
        document.getElementById("loginPage");

    if (!loginPage) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | ELEMENT
    |--------------------------------------------------------------------------
    */

    const roleCards =
        document.querySelectorAll(".role-card");

    const loginTab =
        document.getElementById("loginTab");

    const registerTab =
        document.getElementById("registerTab");

    const loginForm =
        document.getElementById("loginForm");

    const registerForm =
        document.getElementById("registerForm");

    const loginButton =
        document.getElementById("loginButton");

    const registerButton =
        document.getElementById("registerButton");

    const demoAccount =
        document.getElementById("demoAccount");

    const logoColor =
        document.getElementById("logoColor");


    /*
    |--------------------------------------------------------------------------
    | ROLE DATA
    |--------------------------------------------------------------------------
    */

    const roles = {

        kasir: {
            name: "Kasir",
            color: "#b84e27",

            demoUsername: "kasir",
            demoPassword: "kasir123"
        },

        pic: {
            name: "PIC",
            color: "#2558d7",

            demoUsername: "pic",
            demoPassword: "pic123"
        },

        dapur: {
            name: "Dapur",
            color: "#2d8b5d",

            demoUsername: "dapur",
            demoPassword: "dapur123"
        }

    };


    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */

    let currentRole = "kasir";

    let currentMode = "login";


    /*
    |--------------------------------------------------------------------------
    | INITIAL
    |--------------------------------------------------------------------------
    */

    updateRole(currentRole);

    showLogin();


    /*
    |--------------------------------------------------------------------------
    | PILIH ROLE
    |--------------------------------------------------------------------------
    */

    roleCards.forEach((card) => {

        card.addEventListener("click", () => {

            const role =
                card.dataset.role;

            currentRole = role;

            updateRole(role);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | UPDATE ROLE
    |--------------------------------------------------------------------------
    */

    function updateRole(role) {

        const data =
            roles[role];


        /*
        | Active card
        */

        roleCards.forEach((card) => {

            card.classList.remove("active");

        });


        const selectedCard =
            document.querySelector(
                `.role-card[data-role="${role}"]`
            );


        if (selectedCard) {

            selectedCard.classList.add("active");

        }


        /*
        | Warna halaman
        */

        loginPage.classList.remove(
            "role-kasir",
            "role-pic",
            "role-dapur"
        );


        loginPage.classList.add(
            `role-${role}`
        );


        /*
        | Warna logo
        */

        logoColor.style.color =
            data.color;


        /*
        | Tombol berdasarkan mode
        */

        if (currentMode === "login") {

            loginButton.textContent =
                `Masuk sebagai ${data.name}`;

        } else {

            registerButton.textContent =
                `Daftar sebagai ${data.name}`;

        }


        /*
        | Demo account
        */

        if (currentMode === "login") {

            demoAccount.innerHTML =
                `Belum punya akun? Pilih <b>Daftar</b>`;

        } else {

            demoAccount.innerHTML =
                `Sudah punya akun? Pilih <b>Masuk</b>`;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | TAB MASUK
    |--------------------------------------------------------------------------
    */

    loginTab.addEventListener("click", () => {

        showLogin();

    });


    /*
    |--------------------------------------------------------------------------
    | TAB DAFTAR
    |--------------------------------------------------------------------------
    */

    registerTab.addEventListener("click", () => {

        showRegister();

    });


    /*
    |--------------------------------------------------------------------------
    | SHOW LOGIN
    |--------------------------------------------------------------------------
    */

    function showLogin() {

        currentMode = "login";


        loginTab.classList.add("active");

        registerTab.classList.remove("active");


        loginForm.style.display = "block";

        registerForm.style.display = "none";


        const data =
            roles[currentRole];


        loginButton.textContent =
            `Masuk sebagai ${data.name}`;


        demoAccount.innerHTML =
            `Belum punya akun? Pilih <b>Daftar</b>`;

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW REGISTER
    |--------------------------------------------------------------------------
    */

    function showRegister() {

        currentMode = "register";


        registerTab.classList.add("active");

        loginTab.classList.remove("active");


        loginForm.style.display = "none";

        registerForm.style.display = "block";


        const data =
            roles[currentRole];


        registerButton.textContent =
            `Daftar sebagai ${data.name}`;


        demoAccount.innerHTML =
            `Sudah punya akun? Pilih <b>Masuk</b>`;

    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN SUBMIT
    |--------------------------------------------------------------------------
    */

    loginForm.addEventListener("submit", (event) => {

        event.preventDefault();


        /*
        | BELUM ADA LOGIN DATABASE
        | Tahap ini hanya UI
        */

        console.log(
            `Login sebagai ${currentRole}`
        );

    });


    /*
    |--------------------------------------------------------------------------
    | REGISTER SUBMIT
    |--------------------------------------------------------------------------
    */

    registerForm.addEventListener("submit", (event) => {

        event.preventDefault();


        /*
        | BELUM ADA DATABASE
        | Tahap ini hanya UI
        */

        console.log(
            `Register sebagai ${currentRole}`
        );

    });

});
