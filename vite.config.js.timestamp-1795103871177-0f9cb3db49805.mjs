// vite.config.js
import { defineConfig } from "file:///C:/xampp/htdocs/tazen_web/public_html/node_modules/vite/dist/node/index.js";
import laravel from "file:///C:/xampp/htdocs/tazen_web/public_html/node_modules/laravel-vite-plugin/dist/index.js";
import tailwindcss from "file:///C:/xampp/htdocs/tazen_web/public_html/node_modules/@tailwindcss/vite/dist/index.mjs";
var vite_config_default = defineConfig({
  // Bind IPv4 explicitly. Default [::1] breaks auth CSS when the app is opened via 127.0.0.1.
  server: {
    host: "127.0.0.1",
    port: 5173,
    strictPort: true
  },
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/js/app.js",
        "resources/css/tazen.css",
        "resources/css/auth-theme.css",
        "resources/css/professional-panel.css",
        "resources/css/customer-panel.css",
        "resources/css/yuktione-feedback.css",
        "resources/js/yuktione-feedback.js",
        "resources/js/customer-registration.js",
        "resources/js/professional-registration.js",
        "resources/js/tazen-v2/panel.js"
      ],
      refresh: true
    }),
    tailwindcss()
  ]
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcdGF6ZW5fd2ViXFxcXHB1YmxpY19odG1sXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ZpbGVuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcdGF6ZW5fd2ViXFxcXHB1YmxpY19odG1sXFxcXHZpdGUuY29uZmlnLmpzXCI7Y29uc3QgX192aXRlX2luamVjdGVkX29yaWdpbmFsX2ltcG9ydF9tZXRhX3VybCA9IFwiZmlsZTovLy9DOi94YW1wcC9odGRvY3MvdGF6ZW5fd2ViL3B1YmxpY19odG1sL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB0YWlsd2luZGNzcyBmcm9tICdAdGFpbHdpbmRjc3Mvdml0ZSc7XG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XG4gICAgLy8gQmluZCBJUHY0IGV4cGxpY2l0bHkuIERlZmF1bHQgWzo6MV0gYnJlYWtzIGF1dGggQ1NTIHdoZW4gdGhlIGFwcCBpcyBvcGVuZWQgdmlhIDEyNy4wLjAuMS5cbiAgICBzZXJ2ZXI6IHtcbiAgICAgICAgaG9zdDogJzEyNy4wLjAuMScsXG4gICAgICAgIHBvcnQ6IDUxNzMsXG4gICAgICAgIHN0cmljdFBvcnQ6IHRydWUsXG4gICAgfSxcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFtcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy9hcHAuY3NzJyxcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL2FwcC5qcycsXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvdGF6ZW4uY3NzJyxcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy9hdXRoLXRoZW1lLmNzcycsXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvcHJvZmVzc2lvbmFsLXBhbmVsLmNzcycsXG4gICAgICAgICAgICAgICAgJ3Jlc291cmNlcy9jc3MvY3VzdG9tZXItcGFuZWwuY3NzJyxcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2Nzcy95dWt0aW9uZS1mZWVkYmFjay5jc3MnLFxuICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvanMveXVrdGlvbmUtZmVlZGJhY2suanMnLFxuICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvanMvY3VzdG9tZXItcmVnaXN0cmF0aW9uLmpzJyxcbiAgICAgICAgICAgICAgICAncmVzb3VyY2VzL2pzL3Byb2Zlc3Npb25hbC1yZWdpc3RyYXRpb24uanMnLFxuICAgICAgICAgICAgICAgICdyZXNvdXJjZXMvanMvdGF6ZW4tdjIvcGFuZWwuanMnLFxuICAgICAgICAgICAgXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgIH0pLFxuICAgICAgICB0YWlsd2luZGNzcygpLFxuICAgIF0sXG59KTtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBMlMsU0FBUyxvQkFBb0I7QUFDeFUsT0FBTyxhQUFhO0FBQ3BCLE9BQU8saUJBQWlCO0FBRXhCLElBQU8sc0JBQVEsYUFBYTtBQUFBO0FBQUEsRUFFeEIsUUFBUTtBQUFBLElBQ0osTUFBTTtBQUFBLElBQ04sTUFBTTtBQUFBLElBQ04sWUFBWTtBQUFBLEVBQ2hCO0FBQUEsRUFDQSxTQUFTO0FBQUEsSUFDTCxRQUFRO0FBQUEsTUFDSixPQUFPO0FBQUEsUUFDSDtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxRQUNBO0FBQUEsUUFDQTtBQUFBLFFBQ0E7QUFBQSxNQUNKO0FBQUEsTUFDQSxTQUFTO0FBQUEsSUFDYixDQUFDO0FBQUEsSUFDRCxZQUFZO0FBQUEsRUFDaEI7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=
