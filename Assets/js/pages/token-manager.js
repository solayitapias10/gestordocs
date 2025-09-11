/**
 * Gestor de tokens mejorado
 * Versión optimizada para manejo robusto de tokens JWT
 */

const TOKEN_MANAGER = {
    // Configuración
    SAFETY_MARGIN: 5 * 60, 
    // Obtener token de múltiples fuentes
    getToken() {
        return (
            localStorage.getItem("token") ||
            sessionStorage.getItem("token") ||
            this.getTokenFromCookie()
        );
    },

    // Obtener token desde cookie
    getTokenFromCookie() {
        const cookies = document.cookie.split(";");
        const tokenCookie = cookies.find((cookie) =>
            cookie.trim().startsWith("token=")
        );
        return tokenCookie ? tokenCookie.split("=")[1] : null;
    },

    // Verificar expiración del token
    checkTokenExpiration() {
        const token = this.getToken();
        const expiresAt =
            localStorage.getItem("expires_at") ||
            sessionStorage.getItem("expires_at");

        if (!token || !expiresAt) {
            console.warn("No hay token válido");
            this.redirectToLogin();
            return false;
        }

        const currentTime = Math.floor(Date.now() / 1000);
        const expirationTime = parseInt(expiresAt);

        // Calcular tiempo restante
        const timeRemaining = expirationTime - currentTime;

        if (timeRemaining <= 0) {
            console.warn("Token expirado");
            this.redirectToLogin();
            return false;
        }

        if (timeRemaining <= this.SAFETY_MARGIN) {
            console.log("Renovando token automáticamente");
            this.renewToken();
        }

        return true;
    },

    // Renovar token
    renewToken() {
        const currentToken = this.getToken();
        if (!currentToken) {
            this.redirectToLogin();
            return;
        }

        fetch(`${base_url}principal/renovarToken`, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${currentToken}`,
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((res) => {
                if (res.tipo === "success") {
                    // Actualizar tokens en múltiples almacenamientos
                    localStorage.setItem("token", res.token);
                    sessionStorage.setItem("token", res.token);
                    localStorage.setItem("expires_at", res.expires_at);
                    sessionStorage.setItem("expires_at", res.expires_at);

                    // Actualizar cookie
                    document.cookie = `token=${res.token}; path=/; expires=${new Date(
                        parseInt(res.expires_at) * 1000
                    ).toUTCString()}; SameSite=Strict; Secure`;

                    console.log("Token renovado exitosamente");
                } else {
                    console.error("Error al renovar token:", res.mensaje);
                    this.redirectToLogin();
                }
            })
            .catch((error) => {
                console.error("Error en renovación de token:", error);
                this.redirectToLogin();
            });
    },

    // Redirigir al login
    redirectToLogin() {
        // Limpiar todos los tokens
        localStorage.removeItem("token");
        localStorage.removeItem("expires_at");
        sessionStorage.removeItem("token");
        sessionStorage.removeItem("expires_at");

        // Eliminar cookie
        document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

        // Redirigir
        window.location = base_url;
    },

    // Inicializar gestor de tokens
    init() {
        // Verificar token al inicio
        this.checkTokenExpiration();

        // Verificar periódicamente
        setInterval(() => this.checkTokenExpiration(), 60000); // Cada minuto

        // Configurar interceptores de solicitudes
        this.setupRequestInterceptors();
    },

    // Configurar interceptores para añadir token a solicitudes
    setupRequestInterceptors() {
        const originalFetch = window.fetch;
        window.fetch = (url, options = {}) => {
            const token = this.getToken();
            if (token) {
                options.headers = {
                    ...options.headers,
                    Authorization: `Bearer ${token}`,
                };
            }
            return originalFetch(url, options);
        };

        // Interceptor para XMLHttpRequest
        const originalOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function () {
            const token = TOKEN_MANAGER.getToken();
            if (token) {
                this.setRequestHeader("Authorization", `Bearer ${token}`);
            }
            return originalOpen.apply(this, arguments);
        };
    },
};

// Iniciar al cargar documento
document.addEventListener("DOMContentLoaded", () => TOKEN_MANAGER.init());

// Exponer para debugging global
window.tokenManager = TOKEN_MANAGER;
