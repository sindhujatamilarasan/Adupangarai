package com.adupangarai.app;

import android.content.Context;
import android.print.PrintAttributes;
import android.print.PrintManager;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

/**
 * Prints the current screen (e.g. the weekly meal plan) through Android's print service.
 * The print dialog also offers "Save as PDF", so this covers printing and PDF download.
 */
@CapacitorPlugin(name = "Print")
public class PrintPlugin extends Plugin {

    @PluginMethod
    public void print(PluginCall call) {
        String name = call.getString("name", "Adupangarai");
        getActivity().runOnUiThread(() -> {
            PrintManager printManager = (PrintManager) getContext().getSystemService(Context.PRINT_SERVICE);
            PrintAttributes attributes = new PrintAttributes.Builder()
                .setMediaSize(PrintAttributes.MediaSize.ISO_A4.asLandscape())
                .build();
            printManager.print(name, getBridge().getWebView().createPrintDocumentAdapter(name), attributes);
            call.resolve();
        });
    }
}
