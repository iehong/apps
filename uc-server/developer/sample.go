// UC API 示例 Golang 语言

package main

import (
    "bytes"
    "crypto/rand"
    "crypto/sha256"
    "encoding/base64"
    "encoding/json"
    "encoding/hex"
    "fmt"
    "io/ioutil"
    "math/big"
    "net/http"
    "strconv"
    "time"
)

type Request struct {
    url    string
    appid  string
    secret string
    token  string
}

func (r *Request) Sample() {
    err := r.getToken()
    if err != nil {
        fmt.Println("Error:", err)
        return
    }

    err = r.loginUser()
    if err != nil {
        fmt.Println("Error:", err)
        return
    }

    err = r.getUser()
    if err != nil {
        fmt.Println("Error:", err)
        return
    }
}

func (r *Request) getToken() error {
    resp, err := r.request("/token", map[string]interface{}{})
    if err != nil {
        return err
    }

    if resp.Ret > 0 || resp.Token == "" {
        return fmt.Errorf("接口错误，无法获取 Token")
    }

    r.token = resp.Token
    return nil
}

func (r *Request) loginUser() error {
    resp, err := r.request("/user/login", map[string]interface{}{
        "username": "test",
        "password": "1",
    })
    if err != nil {
        return err
    }

    if resp.Ret > 0 {
        return fmt.Errorf("接口错误，无法获取 /user/login")
    }

    fmt.Println(resp)
    return nil
}

func (r *Request) getUser() error {
    resp, err := r.request("/user/get_user", map[string]interface{}{
        "username": "test",
    })
    if err != nil {
        return err
    }

    if resp.Ret > 0 {
        return fmt.Errorf("接口错误，无法获取 /user/get_user")
    }

    fmt.Println(resp)
    return nil
}

func (r *Request) request(uri string, post map[string]interface{}) (*Response, error) {
    nonce, err := rand.Int(rand.Reader, big.NewInt(1000))
    if err != nil {
        return nil, err
    }
    nonceStr := strconv.Itoa(int(nonce.Int64()) + 1000)

    t := strconv.FormatInt(time.Now().Unix(), 10)
    sign := r.generateSign(nonceStr, t)

    headers := map[string]string{
        "appid": r.appid,
        "nonce": nonceStr,
        "t":     t,
        "sign":  sign,
    }

    if r.token != "" {
        headers["token"] = r.token
    }

    jsonData, err := json.Marshal(post)
    if err != nil {
        return nil, err
    }

    req, err := http.NewRequest("POST", r.url + "?" + uri, bytes.NewBuffer(jsonData))
    if err != nil {
        return nil, err
    }

    for key, value := range headers {
        req.Header.Set(key, value)
    }

    client := &http.Client{}
    resp, err := client.Do(req)
    if err != nil {
        return nil, err
    }
    defer resp.Body.Close()

    if resp.StatusCode != http.StatusOK {
        return nil, fmt.Errorf("Network response was not ok")
    }

    body, err := ioutil.ReadAll(resp.Body)
    if err != nil {
        return nil, err
    }

    var response Response
    err = json.Unmarshal(body, &response)
    if err != nil {
        return nil, err
    }

    return &response, nil
}

func (r *Request) generateSign(nonce, t string) string {
    dataToHash := nonce + t + r.secret
    hashed := sha256.Sum256([]byte(dataToHash))
    return base64.StdEncoding.EncodeToString([]byte(hex.EncodeToString(hashed[:])))
}

type Response struct {
    Ret   int         `json:"ret"`
    Token string      `json:"token"`
    Data  interface{} `json:"data"`
}

func main() {
    r := &Request{
        url:    "http://127.0.0.1/uc_core/upload/api/",
        appid:  "14664096",
        secret: "E9JOFOFEQQSSZFLE",
    }
    r.Sample()
}