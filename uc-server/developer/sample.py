# UC API 示例 Python 语言
import requests
import time
import random
import hashlib
import base64

class Request:
    def __init__(self):
        self.url = 'http://127.0.0.1/uc_core/upload/api/'
        self.appid = '14664096'
        self.secret = 'E9JOFOFEQQSSZFLE'
        self.token = ''

    def sample(self):
        ret = self._request('/token', {})
        if not ret or ret['ret'] > 0 or not ret.get('token'):
            raise Exception('接口错误，无法获取 Token')
        self.token = ret['token']

        ret = self._request('/user/login', {
            'username': 'test',
            'password': '1',
        })
        if not ret or ret['ret'] > 0:
            raise Exception('接口错误，无法获取 /user/login')
        print(ret)

        ret = self._request('/user/get_user', {
            'username': 'test',
        })
        if not ret or ret['ret'] > 0:
            raise Exception('接口错误，无法获取 /user/get_user')
        print(ret)

    def _request(self, uri, post):
        nonce = random.randint(1000, 2000)
        t = int(time.time())
        headers = {
            'appid': self.appid,
            'nonce': str(nonce),
            't': str(t),
            'sign': base64.b64encode(hashlib.sha256((str(nonce) + str(t) + self.secret).encode()).hexdigest().encode()).decode(),
        }

        if self.token:
            headers['token'] = self.token

        response = requests.post(
            self.url + "?" + uri,
            headers=headers,
            data=post,
            verify=False
        )
        return response.json()

# 使用示例
if __name__ == "__main__":
    r = Request()
    r.sample()
