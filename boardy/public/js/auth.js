import { generateVerifier, generateChallenge, generateState } from './pkce.js'

const CLIENT_ID = '01a0a12f-b035-7291-8688-3a175e1ecae2'
const REDIRECT_URI = window.location.origin + '/oauth/callback'

export async function startLogin() {
    const verifier = generateVerifier()
    const challenge = await generateChallenge(verifier)
    const state = generateState()

    sessionStorage.setItem('pkce_verifier', verifier)
    sessionStorage.setItem('oauth_state', state)

    const params = new URLSearchParams({
        client_id: CLIENT_ID,
        response_type: 'code',
        redirect_uri: REDIRECT_URI,
        code_challenge: challenge,
        code_challenge_method: 'S256',
        state: state,
        scope: '*',
    })

    window.location = '/oauth/authorize?' + params
}

export async function handleCallback() {
    const params = new URLSearchParams(window.location.search)
    const code = params.get('code')
    const state = params.get('state')

    if (!code) return null

    // Проверка state — защита от CSRF
    const savedState = sessionStorage.getItem('oauth_state')
    if (state !== savedState) {
        throw new Error('Invalid state — CSRF attack?')
    }

    const verifier = sessionStorage.getItem('pkce_verifier')
    if (!verifier) throw new Error('No verifier')

    // Обмен code на токены
    const res = await fetch('/oauth/token', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'include',
        body: JSON.stringify({
            grant_type: 'authorization_code',
            client_id: CLIENT_ID,
            code, code_verifier: verifier,
            redirect_uri: REDIRECT_URI,
        })
    })
    const data = await res.json()

    sessionStorage.removeItem('pkce_verifier')
    sessionStorage.removeItem('oauth_state')

    return data.access_token
}

export async function refreshToken() {
    // refresh_token в HttpOnly cookie — браузер пошлёт сам
    const res = await fetch('/oauth/token', {
        method: 'POST',
        credentials: 'include',     // важно — куки летят
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            grant_type: 'refresh_token',
            client_id: CLIENT_ID,
        })
    })
    if (!res.ok) {
        // refresh протух — редирект на логин
        startLogin()
        return null
    }
    const data = await res.json()
    return data.access_token
}
