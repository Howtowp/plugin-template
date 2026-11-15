import { createRoot } from "react-dom/client";
import { App } from "./App";
import "./styles.css";

const root = document.getElementById("htw-pt-admin");
const config = window.htwPtAdmin;

if (root && config) {
  createRoot(root).render(<App config={config} />);
}
