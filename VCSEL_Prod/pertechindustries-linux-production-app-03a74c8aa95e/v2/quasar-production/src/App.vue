<template>
  <router-view />
</template>

<script>
import { defineComponent } from "vue";
import { useRouter } from "vue-router";
import { useConfigStore } from "stores/config-store";
import { AppFullscreen, Notify, Dialog, BottomSheet, Loading } from "quasar";
import packageInfo from "../package.json";

export default defineComponent({
  name: "App",
  setup() {
    const router = useRouter();
    const configStore = useConfigStore();
    return {
      router,
      configStore,
      version: packageInfo.version,
      appIsFullscreen: false,
    };
  },
  // ============================================= //
  mounted: function () {
    // Handle actions from electron menu bar
    if (window.ipcRenderer?.on) {
      window.ipcRenderer.on("config", (event) => {
        this.router.push("/config");
      });
      window.ipcRenderer.on("about", (event) => {
        Dialog.create({
          title: "About",
          message: "Part Number: 109118A<br/>Version: " + this.version,
          html: true,
        });
      });
    }

    // Load stored config
    this.configStore.loadConfig();
    this.configStore.loadExpansionStates();

    // Expose Quasar Plugins for remote calling
    window.AppFullscreen = AppFullscreen;
    window.Notify = Notify;
    window.Dialog = Dialog;
    window.BottomSheet = BottomSheet;
    window.Loading = Loading;
  },
});
</script>
