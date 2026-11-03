<!doctype html><html lang="{{ $language }}"><body>
<h1>Charlot Crea</h1>
<p>{{ $language === 'tr' ? 'Müşteri paneli parolanızı belirlemek için aşağıdaki bağlantıyı kullanın.' : 'Use the link below to set your client portal password.' }}</p>
<p><a href="{{ $resetUrl }}">{{ $language === 'tr' ? 'Parolamı belirle' : 'Set my password' }}</a></p>
<p>{{ $language === 'tr' ? 'Bağlantı 60 dakika geçerlidir. Bu isteği siz yapmadıysanız mesajı dikkate almayabilirsiniz.' : 'This link expires in 60 minutes. If you did not request it, you can ignore this message.' }}</p>
</body></html>
