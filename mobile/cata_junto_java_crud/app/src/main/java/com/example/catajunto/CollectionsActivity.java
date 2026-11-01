package com.example.catajunto;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class CollectionsActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private ProgressBar progressBar;
    private TextView textError;
    private Button btnLogout, btnRetry;
    private CollectionAdapter adapter;
    private final List<JSONObject> pendingCollections = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_collections);

        recyclerView = findViewById(R.id.recyclerViewCollections);
        progressBar = findViewById(R.id.progressBarCollections);
        textError = findViewById(R.id.textErrorCollections);
        btnLogout = findViewById(R.id.btnLogout);
        btnRetry = findViewById(R.id.btnRetryCollections);

        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new CollectionAdapter(pendingCollections);
        recyclerView.setAdapter(adapter);

        btnLogout.setOnClickListener(v -> logout());
        btnRetry.setOnClickListener(v -> loadCollections());

        loadCollections();
    }

    private void loadCollections() {
        setLoading(true);
        textError.setVisibility(View.GONE);
        btnRetry.setVisibility(View.GONE);

        ExecutorService executor = Executors.newSingleThreadExecutor();
        executor.execute(() -> {
            try {
                URL url = new URL(MainActivity.K_BASE_URL + "/available-residential-collections");
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setRequestProperty("User-Agent", MainActivity.K_USER_AGENT);
                conn.setRequestProperty("Accept", "application/json, text/plain, */*");
                conn.setRequestProperty("Accept-Language", "pt-BR,pt;q=0.9,en;q=0.8");
                conn.setRequestProperty("Origin", MainActivity.K_BASE_URL);
                conn.setRequestProperty("Referer", MainActivity.K_BASE_URL + "/");

                if (MainActivity.generatedCookie != null) {
                    conn.setRequestProperty("Cookie", MainActivity.generatedCookie);
                }

                int responseCode = conn.getResponseCode();
                BufferedReader reader;
                if (responseCode >= 400) {
                    reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
                } else {
                    reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                }

                StringBuilder response = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    response.append(line.trim());
                }
                reader.close();

                String resBody = response.toString();

                runOnUiThread(() -> {
                    setLoading(false);

                    if (resBody.contains("<script")) {
                        showError("Servidor inacessível. Reabra o app.");
                        return;
                    }

                    if (responseCode == 401) {
                        redirectToLogin();
                        return;
                    }

                    if (responseCode == 200) {
                        try {
                            pendingCollections.clear();
                            JSONArray jsonArray = new JSONArray(resBody);
                            for (int i = 0; i < jsonArray.length(); i++) {
                                JSONObject obj = jsonArray.getJSONObject(i);
                                if ("pending".equals(obj.optString("status"))) {
                                    pendingCollections.add(obj);
                                }
                            }
                            adapter.notifyDataSetChanged();

                            if (pendingCollections.isEmpty()) {
                                showError("Nenhuma coleta pendente.");
                            }
                        } catch (Exception e) {
                            showError("Erro ao processar dados.");
                        }
                    } else {
                        showError("Falha ao carregar (" + responseCode + ").");
                    }
                });

            } catch (Exception e) {
                runOnUiThread(() -> {
                    setLoading(false);
                    showError("Erro de rede ou conexão.");
                });
            }
        });
    }

    private void logout() {
        ExecutorService executor = Executors.newSingleThreadExecutor();
        executor.execute(() -> {
            try {
                URL url = new URL(MainActivity.K_BASE_URL + "/logout");
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                if (MainActivity.generatedCookie != null) {
                    conn.setRequestProperty("Cookie", MainActivity.generatedCookie);
                }
                conn.getResponseCode();
            } catch (Exception ignored) {}
        });
        redirectToLogin();
    }

    private void redirectToLogin() {
        Intent intent = new Intent(CollectionsActivity.this, MainActivity.class);
        startActivity(intent);
        finish();
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        if (loading) {
            recyclerView.setVisibility(View.GONE);
        } else {
            recyclerView.setVisibility(View.VISIBLE);
        }
    }

    private void showError(String msg) {
        textError.setText(msg);
        textError.setVisibility(View.VISIBLE);
        btnRetry.setVisibility(View.VISIBLE);
        recyclerView.setVisibility(View.GONE);
    }
}