<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>CDN Proxy Test</title>
</head>
<body>
  <h1>CDN Proxy テスト</h1>
  <h2>旧パターン</h2>
  <form method="get" action="./">
    <label>取得したいURL: <input type="text" name="url" size="60" value="https://ai-gazoukan.com/wp/wp-content/uploads/2025/08/00000-3199285662.jpg"></label>
    <button type="submit">取得</button>
  </form>
  <h2>新パターン (JavaScript)</h2>
  <div>
    <label>取得したいURL: <input type="text" id="urlInput" size="60" value="https://ai-gazoukan.com/wp/wp-content/uploads/2025/08/00000-3199285662.jpg"></label>
    <button type="button" onclick="redirectToUrl()">取得</button>
  </div>

  <script>
    function redirectToUrl() {
      const url = document.getElementById('urlInput').value;
      
      if (!url) {
        alert('URLを入力してください');
        return;
      }
      
      // URLをエンコードしてリダイレクト
      const encodedUrl = encodeURIComponent(url);
      location.href = `./url/${encodedUrl}`;
    }
  </script>
</body>
</html>
