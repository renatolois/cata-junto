package com.example.catajunto;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import org.json.JSONObject;
import java.util.List;

public class CollectionAdapter extends RecyclerView.Adapter<CollectionAdapter.ViewHolder> {

    private final List<JSONObject> collections;

    public CollectionAdapter(List<JSONObject> collections) {
        this.collections = collections;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(android.R.layout.simple_list_item_2, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        JSONObject c = collections.get(position);
        holder.text1.setText("Material #" + c.optString("material_type_id"));
        holder.text2.setText("Tipo: " + c.optString("collect_type", "-") +
                "\nDescrição: " + c.optString("description", "-") +
                "\nSolicitado em: " + c.optString("requested_at", "-"));
    }

    @Override
    public int getItemCount() {
        return collections.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView text1, text2;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            text1 = itemView.findViewById(android.R.id.text1);
            text2 = itemView.findViewById(android.R.id.text2);
        }
    }
}