/********************************************
Script theme.js                         
Creado por el equipo Gaes 1:            
Anyi Solayi Tapias                  
Sharit Delgado Pinzón               
Durly Yuranni Sánchez Carillo       
Año: 2025                              
SENA - CSET - ADSO                    
 ********************************************/

document.addEventListener("DOMContentLoaded", function () {
    const toggleThemeButton = document.querySelector(".toggle-theme");
    const themeIcon = toggleThemeButton.querySelector(".theme-icon");
    const darkThemeStylesheet = document.getElementById("dark-theme");

    // Aplica el tema (oscuro o claro) a la página
    function applyTheme(theme) {
        if (theme === "dark") {
            darkThemeStylesheet.disabled = false;
            document.body.classList.add("dark-mode");
            document.body.classList.remove("light-mode");
            themeIcon.textContent = "light_mode";
        } else {
            darkThemeStylesheet.disabled = true;
            document.body.classList.add("light-mode");
            document.body.classList.remove("dark-mode");
            themeIcon.textContent = "dark_mode";
        }
    }

    // Obtiene el tema guardado en el almacenamiento local y lo aplica
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);

    // Escucha el clic en el botón para alternar entre el tema claro y oscuro
    if (toggleThemeButton) {
        toggleThemeButton.addEventListener("click", function (e) {
            e.preventDefault();
            const currentTheme = !darkThemeStylesheet.disabled ? "dark" : "light";
            const newTheme = currentTheme === "light" ? "dark" : "light";

            applyTheme(newTheme);
            localStorage.setItem("theme", newTheme);
        });
    }
});