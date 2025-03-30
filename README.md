
# Dai

[Dai](https://github.com/ououduck/Dai) 是由 D 工作室开发的一个网页 AI 项目，旨在通过接入多个大模型来提供智能对话功能。

## 目录

- [介绍](#介绍)
- [功能](#功能)
- [使用说明](#使用说明)
  - [环境要求](#环境要求)
  - [配置说明](#配置说明)
- [文件结构](#文件结构)
- [许可证](#许可证)

## 介绍

Dai 是一个用于网页的 AI 聊天应用，支持多种 AI 模型的接入和切换。用户可以通过简单的界面与 AI 进行对话，并且支持联网搜索功能，提供更丰富的回答内容。

## 功能

- **多模型支持**：可以选择不同的 AI 模型进行对话。
- **聊天记录**：保存聊天记录，方便用户查看历史对话。
- **联网搜索**：支持通过 Google Custom Search API 进行联网搜索，提供更丰富的回答。
- **响应式设计**：适配多种设备，提供良好的用户体验。
- **Markdown 渲染**：支持 Markdown 格式的内容渲染。

## 使用说明

### 环境要求

在开始安装之前，请确保您的环境满足以下要求：

- PHP 7.4 或更高版本
- 网络连接（用于联网搜索）
- 支持 PHP 的 Web 服务器（如 Apache 或 Nginx）

### 配置说明

1. **配置API**：

   打开 `api.php` 文件 替换 api接口 和 密钥 

2. **配置模型选择**：

   如果需要添加更多模型，请编辑 `index.php` 文件中的模型选择部分，按以下格式添加模型选项：

   ```html
   <select id="model-select">
     <option value="model-identifier">模型名称</option>
   </select>
   ```

## 文件结构

```
Dai/
├── LICENSE                 # 许可证文件
├── ai.svg                  # AI 头像图片
├── api.php                 # 处理搜索请求和聊天请求的 PHP 文件
├── favicon.ico             # 网站图标
├── index.php               # 项目主页面
├── style.css               # 样式文件
```

## 许可证

Dai 使用 [MIT 许可证](LICENSE) 开源。

---

© 2025 D 工作室 & Duck

更多信息请访问 [使用文档](https://www.dduck.fun/posts/help-for-dai.html) 或 [GitHub 仓库](https://github.com/ououduck/Dai)。
```
