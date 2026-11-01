package com.example.catajunto;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends AppCompatActivity {

    public static final String K_BASE_URL = "https://cata-junto.wuaze.com";
    public static final String K_USER_AGENT = "Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";

    public static String generatedCookie = null;
    private final InfinityfreeBypasser bypasser = new InfinityfreeBypasser();

    private EditText inputEmail, inputPassword;
    private Button btnLogin;
    private ProgressBar progressBar;
    private TextView textError;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main); // Certifique-se de ter o layout correspondente

        inputEmail = findViewById(R.id.inputEmail);
        inputPassword = findViewById(R.id.inputPassword);
        btnLogin = findViewById(R.id.btnLogin);
        progressBar = findViewById(R.id.progressBar);
        textError = findViewById(R.id.textError);

        // Desativa controles até o bypass rodar
        setLoading(true);
        textError.setText("Conectando ao servidor...");

        // Executa o Bypass em background (equivalente ao main async do Flutter)
        ExecutorService executor = Executors.newSingleThreadExecutor();
        executor.execute(() -> {
            try {
                bypasser.bypass(K_BASE_URL + "/person/me");
                generatedCookie = bypasser.getCookie();
            } catch (Exception e) {
                e.printStackTrace();
            }

            runOnUiThread(() -> {
                setLoading(false);
                textError.setText("");
                if (generatedCookie == null) {
                    textError.setText("Aviso: Falha ao obter cookie de segurança.");
                }
            });
        });

        btnLogin.setOnClickListener(v -> performLogin());
    }

    private void performLogin() {
        String email = inputEmail.getText().toString().trim();
        String password = inputPassword.getText().toString().trim();

        if (email.isEmpty() || password.isEmpty()) {
            textError.setText("Preencha todos os campos.");
            return;
        }

        setLoading(true);
        textError.setText("");

        ExecutorService executor = Executors.newSingleThreadExecutor();
        executor.execute(() -> {
            try {
                URL url = new URL(K_BASE_URL + "/login/person");
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setRequestProperty("User-Agent", K_USER_AGENT);
                conn.setRequestProperty("Accept", "application/json, text/plain, */*");
                conn.setRequestProperty("Accept-Language", "pt-BR,pt;q=0.9,en;q=0.8");
                conn.setRequestProperty("Origin", K_BASE_URL);
                conn.setRequestProperty("Referer", K_BASE_URL + "/");

                if (generatedCookie != null) {
                    conn.setRequestProperty("Cookie", generatedCookie);
                }

                conn.setDoOutput(true);
                String jsonInputString = "{\"email\":\"" + email + "\",\"password\":\"" + password + "\"}";

                try (OutputStream os = conn.getOutputStream()) {
                    byte[] input = jsonInputString.getBytes("utf-8");
                    os.write(input, 0, input.length);
                }

                int responseCode = conn.getResponseCode();
                BufferedReader reader;
                if (responseCode >= 400) {
                    reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
                } else {
                    reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                }

                StringBuilder response = new StringBuilder();
                String responseLine;
                while ((responseLine = reader.readLine()) != null) {
                    response.append(responseLine.trim());
                }
                reader.close();

                String resBody = response.toString();

                runOnUiThread(() -> {
                    setLoading(false);
                    if (resBody.contains("<script")) {
                        textError.setText("Servidor inacessível. Reabra o app.");
                        return;
                    }

                    if (responseCode == 200) {
                        // Login bem sucedido - Redireciona para CollectionsActivity (equivalente ao CollectionsScreen)
                        Intent intent = new Intent(MainActivity.this, CollectionsActivity.class);
                        startActivity(intent);
                        finish();
                    } else if (responseCode == 401) {
                        textError.setText("E-mail ou senha inválidos.");
                    } else if (responseCode == 403) {
                        textError.setText("Conta inativa.");
                    } else {
                        textError.setText("Falha no login (" + responseCode + ").");
                    }
                });

            } catch (Exception e) {
                runOnUiThread(() -> {
                    setLoading(false);
                    textError.setText("Erro de rede ou conexão.");
                });
            }
        });
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? android.view.View.VISIBLE : android.view.View.GONE);
        btnLogin.setEnabled(!loading);
        inputEmail.setEnabled(!loading);
        inputPassword.setEnabled(!loading);
    }
}