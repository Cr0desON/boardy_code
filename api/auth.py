import os
import jwt
from fastapi import Header, HTTPException

key_path = os.getenv('OAUTH_PUBLIC_KEY', 'oauth-public.key')
PUBLIC_KEY = open(key_path).read()

async def get_current_user(authorization: str = Header(None)):
    if not authorization or not authorization.startswith('Bearer '):
        raise HTTPException(401, 'Token required')

    token = authorization.split(' ')[1]
    try:
        payload = jwt.decode(
            token, PUBLIC_KEY,
            algorithms=['RS256'],
            options={'verify_aud': False}
        )
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(401, 'Token expired')
    except jwt.InvalidTokenError as e:
        raise HTTPException(401, f'Invalid token: {e}')
