<template>
  <q-page>
    <div class="q-pa-md">
      <q-list bordered class="rounded-borders">
        <q-expansion-item
          v-for="(server, serverIndex) in products"
          :key="'server' + serverIndex"
          v-model="configStore.testServerExpansionStates[serverIndex]"
          @update:model-value="saveExpansionStates"
          header-class="bg-grey-4 text-bold"
        >
          <template v-slot:header>
            <q-item-section>
              {{ friendlyNames[serverIndex] }}
            </q-item-section>
          </template>

          <q-card>
            <q-card-section>
              <div
                v-for="(product, productIndex) in server.items"
                :key="'product' + serverIndex + '-' + productIndex"
                class="productBtn"
                @click="
                  selectProduct(
                    product.productId,
                    server.serverUrl,
                    product.title,
                    server.serverName,
                    server.serverFriendlyName
                  )
                "
              >
                <div>
                  <q-img
                    :src="product.imgFilename"
                    :alt="product.title"
                    fit="contain"
                    style="width: 90px; height: 90px"
                    class="q-mb-xs"
                  />
                  {{ product.title }}
                </div>
              </div>
            </q-card-section>
          </q-card>
          <q-separator />
        </q-expansion-item>
      </q-list>
    </div>
  </q-page>
</template>

<script>
import { defineComponent, ref } from "vue";
import { useConfigStore } from "stores/config-store";
import { useRouter } from "vue-router";

export default defineComponent({
  name: "IndexPage",
  // ============================================= //
  setup() {
    const configStore = useConfigStore();
    const router = useRouter();
    return {
      configStore,
      router,
      friendlyNames: ref({}),
      products: ref({}),
    };
  },
  // ============================================= //
  async mounted() {
    try {
      // clear selected product if set
      if (window.selectedProduct) {
        window.selectedProduct = {};
      }

      // Try and load all products from the Servers
      for (const server of this.configStore.testServers) {
        const response = await this.$axios.post(
          server.url,
          {
            query: {
              dataset: "products",
              action: "getListOfProducts",
              params: {},
            },
          },
          { headers: { "Content-Type": "application/json" } }
        );

        if (response.data.success) {
          this.friendlyNames[response.data.serverName] =
            response.data.serverFriendlyName;
          if (
            this.configStore.testServerExpansionStates[
              response.data.serverName
            ] === undefined
          ) {
            this.configStore.testServerExpansionStates[
              response.data.serverName
            ] = true;
            this.configStore.saveExpansionStates();
          }
          this.products[response.data.serverName] = {
            items: response.data.products,
            serverUrl: server.url,
            serverName: response.data.serverName,
            serverFriendlyName: response.data.serverFriendlyName,
          };
        }
      }
    } catch (error) {
      console.error(error);
    }
  },
  // ============================================= //
  methods: {
    // --------------------------------------------- //
    saveExpansionStates: async function (value) {
      await this.configStore.saveExpansionStates();
    },
    // --------------------------------------------- //
    selectProduct: function (
      productId,
      serverUrl,
      productTitle,
      serverName,
      serverFriendlyName
    ) {
      window.selectedProduct = {
        productId: productId,
        productTitle: productTitle,
        serverUrl: serverUrl,
        serverName: serverName,
        serverFriendlyName: serverFriendlyName,
      };
      this.router.push("/templateLoader");
    },
    // --------------------------------------------- //
  },
});
</script>
