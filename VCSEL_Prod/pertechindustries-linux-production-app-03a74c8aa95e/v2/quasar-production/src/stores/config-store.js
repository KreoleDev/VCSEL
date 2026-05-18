import { defineStore } from "pinia";

export const useConfigStore = defineStore("config", {
  state: () => ({
    analyticsServer: "http://127.0.0.1:8000/production/v2/analytics/",
    testServers: [
      {
        url: "http://127.0.0.1:8000/production/v2/tests/",
      },
    ],
    testServerExpansionStates: {},
  }),
  // ============================================== //
  actions: {
    async loadConfig() {
      const localAnalyticsServer = await localStorage.getItem(
        "analyticsServer"
      );
      if (localAnalyticsServer) {
        this.analyticsServer = localAnalyticsServer;
        window.analyticsServer = localAnalyticsServer;
      }
      const localTestServers = await localStorage.getItem("testServers");
      if (localTestServers) {
        this.testServers = JSON.parse(localTestServers);
        window.testServers = JSON.parse(localTestServers);
      }
    },
    // ---------------------------------------------- //
    async saveConfig() {
      await localStorage.setItem("analyticsServer", this.analyticsServer);
      await localStorage.setItem(
        "testServers",
        JSON.stringify(this.testServers)
      );
    },
    // ---------------------------------------------- //
    async loadExpansionStates() {
      const localTestServerExpansionStates = await localStorage.getItem(
        "testServerExpansionStates"
      );
      if (localTestServerExpansionStates) {
        this.testServerExpansionStates = JSON.parse(
          localTestServerExpansionStates
        );
      }
    },
    // ---------------------------------------------- //
    async saveExpansionStates() {
      const localObj = Object.assign({}, this.testServerExpansionStates); // Had to do this as item was proxy object and not JSON serializable
      await localStorage.setItem(
        "testServerExpansionStates",
        JSON.stringify(localObj)
      );
    },
    // ---------------------------------------------- //
  },
});
