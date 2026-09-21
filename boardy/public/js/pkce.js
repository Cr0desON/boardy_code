// Генерация code_verifier — случайная строка
export function generateVerifier() {
    const arr = new Uint8Array(32)
    crypto.getRandomValues(arr)
    return base64UrlEncode(arr)
}

// SHA-256(verifier) → base64url
export async function generateChallenge(verifier) {
    const data = new TextEncoder().encode(verifier)
    const hash = await crypto.subtle.digest('SHA-256', data)
    return base64UrlEncode(new Uint8Array(hash))
}

// State для CSRF защиты
export function generateState() {
    return generateVerifier()
}

function base64UrlEncode(bytes) {
    return btoa(String.fromCharCode(...bytes))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '')
}
