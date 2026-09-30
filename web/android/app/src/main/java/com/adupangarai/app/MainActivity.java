package com.adupangarai.app;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(PrintPlugin.class); // app-local plugin, see PrintPlugin.java
        super.onCreate(savedInstanceState);
    }
}
