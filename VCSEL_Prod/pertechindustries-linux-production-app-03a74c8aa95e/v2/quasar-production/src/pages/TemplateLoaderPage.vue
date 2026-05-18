<template>
  <q-page>
    <q-toolbar class="bg-primary text-white shadow-2">
      <q-btn flat label="Back to Products" @click="loadProducts" />
      <q-space />
      <div id="toolbarSegment"></div>
    </q-toolbar>
    <div id="templateWrapper"></div>
  </q-page>
</template>

<script>
import { defineComponent } from "vue";
import { useRouter } from "vue-router";

export default defineComponent({
  name: "TemplateLoaderPage",
  // ============================================= //
  setup() {
    const router = useRouter();
    return {
      router,
    };
  },
  // ============================================= //
  async mounted() {
    try {
      // Ensure screen is cleared
      document.getElementById("templateWrapper").innerHTML = "";
      document.getElementById("toolbarSegment").innerHTML = "";

      // Remove old scripts if they exist
      const oldScript = document.getElementById("templateScript");
      if (oldScript) {
        oldScript.remove();
      }
      const oldGlobalApiScript = document.getElementById("globalApiScript");
      if (oldGlobalApiScript) {
        oldGlobalApiScript.remove();
      }
      const oldApiScript = document.getElementById("apiScript");
      if (oldApiScript) {
        oldApiScript.remove();
      }
      const oldTestScript = document.getElementById("testScript");
      if (oldTestScript) {
        oldTestScript.remove();
      }

      // Request initial template, it is up to the template to handle the rest
      const response = await this.$axios.post(
        window.selectedProduct.serverUrl,
        {
          query: {
            dataset: "products",
            action: "getProductTemplate",
            params: {
              productId: window.selectedProduct.productId,
            },
          },
        },
        { headers: { "Content-Type": "application/json" } }
      );

      if (response.data.success) {
        document.getElementById("templateWrapper").innerHTML =
          response.data.template;

        const script = document.createElement("script");
        script.Type = "text/javascript";
        script.text = response.data.script;
        script.id = "templateScript";
        document.body.appendChild(script);
      }
    } catch (error) {
      console.error(error);
    }
  },
  // ============================================= //
  methods: {
    loadProducts() {
      this.router.push("/");
    },
    // --------------------------------------------- //
  },
});
</script>
