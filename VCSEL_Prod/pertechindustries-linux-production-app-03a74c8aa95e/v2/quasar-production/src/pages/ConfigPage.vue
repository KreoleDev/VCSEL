<template>
  <q-page>
    <div class="q-pa-md">
      <q-btn label="< Back" @click="loadHome" class="text-white bg-primary" />
      <q-card class="q-mt-md">
        <q-card-section class="bg-grey-4">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-h6">Configuration</div>
              <div class="text-subtitle2">Servers with Tests</div>
            </div>
            <div class="col-auto">
              <q-btn
                color="positive"
                round
                flat
                icon="add"
                @click="addServerOption"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section
          v-for="(testServ, testIndex) in testServers"
          :key="'Serv-' + testIndex"
        >
          <div class="row items-center no-wrap">
            <div class="col">
              <q-input
                outlined
                :model-value="testServ.url"
                label="Base URL"
                @change="updateTestServer(testIndex, $event)"
              />
            </div>
            <div class="col-auto">
              <q-btn
                color="negative"
                round
                flat
                icon="delete"
                @click="removeServerOption(testIndex)"
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="bg-grey-4">
          <div class="text-subtitle2">Analytics Server</div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-input outlined v-model="analyticsServer" label="Base URL" />
        </q-card-section>
      </q-card>
      <div class="q-mt-md text-right">
        <q-btn label="Save" @click="saveConfig" class="text-white bg-primary" />
      </div>
    </div>
  </q-page>
</template>

<script>
import { defineComponent, ref } from "vue";
import { useRouter } from "vue-router";
import { useConfigStore } from "stores/config-store";
import { useQuasar } from "quasar";

export default defineComponent({
  name: "ConfigPage",
  setup() {
    const router = useRouter();
    const configStore = useConfigStore();
    const $q = useQuasar();
    return {
      router,
      configStore,
      $q,
      analyticsServer: ref(configStore.analyticsServer),
      testServers: ref(configStore.testServers),
    };
  },
  // ============================================= //
  methods: {
    loadHome: function () {
      this.router.push("/");
    },
    // --------------------------------------------- //
    saveConfig: async function () {
      this.configStore.analyticsServer = this.analyticsServer;
      this.configStore.testServers = this.testServers;
      await this.configStore.saveConfig();
      await this.configStore.loadConfig();
      this.router.push("/");
    },
    // --------------------------------------------- //
    updateTestServer: function (index, value) {
      this.testServers[index].url = value;
    },
    // --------------------------------------------- //
    addServerOption: function () {
      this.testServers.push({ url: "" });
    },
    // --------------------------------------------- //
    removeServerOption: function (index) {
      this.$q
        .dialog({
          title: "Confirm",
          message: "Are you sure you want to remove this server?",
          cancel: true,
          persistent: true,
        })
        .onOk(() => {
          this.testServers.splice(index, 1);
        });
    },
    // --------------------------------------------- //
  },
});
</script>
