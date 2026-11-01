package com.example.catajunto;

import javax.crypto.Cipher;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class InfinityfreeBypasser {

    private final Map<String, String> variables = new HashMap<>();
    private String cookie = null;

    public void bypass(String baseUrl) throws Exception {
        if (cookie != null) return;
        calculateCookie(baseUrl);
    }

    private void calculateCookie(String baseUrl) {
        try {
            URL url = new URL(baseUrl);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36");
            conn.setRequestProperty("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8");
            conn.setRequestProperty("Accept-Language", "pt-BR,pt;q=0.9,en;q=0.8");
            conn.setInstanceFollowRedirects(true);

            int status = conn.getResponseCode();
            BufferedReader reader;
            if (status >= 400) {
                reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
            } else {
                reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
            }

            StringBuilder bodyBuilder = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                bodyBuilder.append(line).append("\n");
            }
            reader.close();

            String body = bodyBuilder.toString();

            Pattern scriptPattern = Pattern.compile("<script[^>]*>(.*?)</script>", Pattern.DOTALL);
            if (!scriptPattern.matcher(body).find()) {
                return;
            }

            for (String varName : new String[]{"a", "b", "c"}) {
                Pattern varPattern = Pattern.compile(varName + "=toNumbers\\(\"([0-9a-fA-F]+)\"\\)");
                Matcher matcher = varPattern.matcher(body);
                if (matcher.find()) {
                    variables.put(varName, matcher.group(1));
                }
            }

            generateByPassCookie();

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void generateByPassCookie() {
        if (!variables.containsKey("a") || !variables.containsKey("b") || !variables.containsKey("c")) {
            return;
        }

        try {
            byte[] a = toNumbers(variables.get("a"));
            byte[] b = toNumbers(variables.get("b"));
            byte[] c = toNumbers(variables.get("c"));

            SecretKeySpec keySpec = new SecretKeySpec(a, "AES");
            IvParameterSpec ivSpec = new IvParameterSpec(b);

            Cipher cipher = Cipher.getInstance("AES/CBC/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE, keySpec, ivSpec);

            byte[] decrypted = cipher.doFinal(c);

            String cookieValue = toHex(decrypted).toLowerCase();
            cookie = "__test=" + cookieValue;

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private byte[] toNumbers(String hex) {
        int len = hex.length();
        byte[] data = new byte[len / 2];
        for (int i = 0; i < len; i += 2) {
            data[i / 2] = (byte) ((Character.digit(hex.charAt(i), 16) << 4)
                    + Character.digit(hex.charAt(i + 1), 16));
        }
        return data;
    }

    private String toHex(byte[] bytes) {
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            sb.append(String.format("%02x", b));
        }
        return sb.toString();
    }

    public String getCookie() {
        return cookie;
    }
}